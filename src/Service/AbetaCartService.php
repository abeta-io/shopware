<?php
declare(strict_types=1);

namespace Abeta\PunchOut\Service;

use Abeta\PunchOut\Event\AbetaCartExportEvent;
use Abeta\PunchOut\Struct\AbetaSession;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AbetaCartService
{
    public function __construct(
        private readonly LoggerService $logger,
        private readonly CartService $cartService,
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $productRepository,
        private readonly EntityRepository $categoryRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getCartData(Request $request, SalesChannelContext $context): array
    {
        $data = [
            'general' => $this->getGeneralData($request, $context),
            ...$this->getCartExportData($context),
        ];

        $event = new AbetaCartExportEvent(
            $this->cartService->getCart($context->getToken(), $context),
            $data,
            $context,
        );
        $this->eventDispatcher->dispatch($event);

        return $event->getData();
    }

    public function getCartExportData(SalesChannelContext $context): array
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);

        return [
            'header' => $this->getHeaderData($cart, $context),
            'products' => $this->getProductData($context),
        ];
    }

    private function getGeneralData(Request $request, SalesChannelContext $context): array
    {
        $abetaSession = $context->getExtension(AbetaSession::EXTENSION_NAME);

        if (!$abetaSession instanceof AbetaSession) {
            if ($this->systemConfigService->get('AbetaPunchOut.config.debug', $context->getSalesChannelId())) {
                $this->logger->addEntry('Abeta punch out', $context->getContext(), null, [
                    'errorMessage' => 'Unable to export cart, punchout session data missing',
                ]);
            }

            throw new \Exception("Unable to export cart, session data missing");
        }

        return [
            'session_id' => $abetaSession->getSessionId(),
            'return_url' => $abetaSession->getReturnUrl(),
        ];
    }

    private function getHeaderData(Cart $cart, SalesChannelContext $context): array
    {
        $delivery = $cart->getDeliveries()->first();

        $vatPercentage = $delivery?->getShippingCosts()->getCalculatedTaxes()->first()?->getTax() ?? 0.00;
        $priceInclVat = $delivery?->getShippingCosts()->getTotalPrice() ?? 0.00;
        $priceExVat = $priceInclVat - $vatPercentage;
        $currencyCode = $context->getCurrency()->getIsoCode();

        $headerData = [
            'total_price_ex_vat' => $priceExVat,
            'total_price_inc_vat' => $priceInclVat,
            'currency' => $currencyCode,
            'order_reference' => '',
            'cart_id' => $context->getToken(),
        ];

        if ($this->systemConfigService->get('AbetaPunchOut.config.exportAtConfirmStep', $context->getSalesChannelId())) {
            $headerData['delivery_address'] = $this->getDeliveryAddressData($context);
        }

        return $headerData;
    }

    private function getDeliveryAddressData(SalesChannelContext $context): ?array
    {
        $address = $context->getShippingLocation()->getAddress();

        if ($address === null) {
            return null;
        }

        return [
            'first_name' => $address->getFirstName(),
            'last_name' => $address->getLastName(),
            'company' => $address->getCompany(),
            'street' => $address->getStreet(),
            'zipcode' => $address->getZipcode(),
            'city' => $address->getCity(),
            'country' => $address->getCountry()?->getIso(),
            'phone_number' => $address->getPhoneNumber(),
        ];
    }

    private function getProductData(SalesChannelContext $salesChannelContext): array
    {
        if (!$salesChannelContext->getCustomer()) {
            return [];
        }

        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        $lineItems = $cart->getLineItems();

        $products = [];
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() === 'customized-products') {
                $products[] = $this->handleCustomizedLineItem($lineItem, $salesChannelContext);

                continue;
            }

            $products[] = [$this->handleProductLineItem($lineItem, $salesChannelContext)];
        }

        $products[] = [$this->getShippingData($cart)];

        return array_merge(...array_filter($products));
    }

    private function handleProductLineItem(LineItem $lineItem, SalesChannelContext $salesChannelContext): ?array
    {
        try {
            $productVatPercentage = $lineItem->getPrice()?->getCalculatedTaxes()->first()?->getTaxRate() ?? 0.00;
            $totalVatRate = 100 + $productVatPercentage;
            $productPriceInclVat = $lineItem->getPrice()?->getTotalPrice() ?? 0.00;
            $productExRatePrice = $productPriceInclVat * $productVatPercentage / $totalVatRate;
            $productPriceExVat = $productPriceInclVat - $productExRatePrice;
            $productPriceOriginal = $productPriceInclVat - $productExRatePrice;

            $productData = [];
            try {
                $productData = $this->getProductById(
                    $lineItem->getReferencedId(),
                    $salesChannelContext->getContext()
                );
            } catch (\Throwable) {
            }

            $sku = $lineItem->getPayload()['productNumber'] ?? $lineItem->getReferencedId();
            if (empty($sku)) {
                $sku = $lineItem->getId();
            }

            return [
                'standard' => [
                    'type' => 'product',
                    'sku' => $sku,
                    'title' => $lineItem->getLabel(),
                    'qty' => $lineItem->getQuantity(),
                    'price_incl_vat' => $productPriceInclVat,
                    'price_ex_vat' => $productPriceExVat,
                    'vat_percentage' => $productVatPercentage,
                    'line_total_ex_vat' => $productPriceOriginal,
                    'original_price_ex_vat' => $productPriceOriginal,
                    'image_url' => $lineItem->getCover()?->getUrl() ?? '',
                ],
                'product_data' => $productData,
                'categories' => $this->getCategoryNames(
                    $lineItem->getPayload()['categoryIds'] ?? [],
                    $salesChannelContext->getContext()
                ),
            ];
        } catch (\Throwable) {
        }

        return null;
    }

    private function handleCustomizedLineItem(LineItem $lineItem, SalesChannelContext $salesChannelContext): ?array
    {
        $products = [];

        $product = null;
        foreach ($lineItem->getChildren()->filterType('product') as $child) {
            $product = $this->handleProductLineItem($child, $salesChannelContext);
            $product['standard']['customized_product_id'] = $lineItem->getId();
        }
        if (!$product) {
            return null;
        }

        $products[] = $product;

        foreach ($lineItem->getChildren()->filterType('customized-products-option') as $child) {
            $data = $this->handleProductLineItem($child, $salesChannelContext);

            foreach ($child->getChildren() as $value) {
                $valueData = $this->handleProductLineItem($value, $salesChannelContext);
                $valueData['standard']['title'] = $data['standard']['title'] . ': ' . $valueData['standard']['title'];
                $valueData['standard']['customized_parent_id'] = $lineItem->getId();

                $products[] = $valueData;
            }
        }

        return $products;
    }

    private function getProductById(string $productId, Context $context): ?ProductEntity
    {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('properties');
        $criteria->addAssociation('media');
        $criteria->addAssociation('cover');
        $criteria->addAssociation('options');
        $criteria->addAssociation('manufacturer');

        return $this->productRepository->search($criteria, $context)->first();
    }

    private function getCategoryNames(array $categoryIds, Context $context): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $criteria = new Criteria($categoryIds);

        return $this->categoryRepository->search($criteria, $context)->map(function (CategoryEntity $category) {
            return $category->getName();
        });
    }

    private function getShippingData(Cart $cart): array
    {
        $delivery = $cart->getDeliveries()->first();

        if ($delivery === null) {
            return [];
        }

        $quantity = $delivery->getShippingCosts()->getQuantity();
        $vatPercentage = $delivery->getShippingCosts()->getCalculatedTaxes()->first()->getTax();
        $taxRate = $delivery->getShippingCosts()->getCalculatedTaxes()->first()->getTaxRate();
        $priceInclVat = $delivery->getShippingCosts()->getTotalPrice();
        $priceExVat = $delivery->getShippingCosts()->getTotalPrice() - $vatPercentage;
        $shippingName = $delivery->getShippingMethod()->getName();
        $shippingSku = $delivery->getShippingMethod()->getTechnicalName() ?: 'shipping';

        return [
            'standard' => [
                'type' => 'shipping',
                'sku' => $shippingSku,
                'title' => $shippingName,
                'qty' => $quantity,
                'price_incl_vat' => $priceInclVat,
                'price_ex_vat' => $priceExVat,
                'vat_percentage' => $taxRate,
                'line_total_ex_vat' => $priceExVat,
                'original_price_ex_vat' => $priceExVat,
            ],
        ];
    }

    public function exportCartData(string $postUrl, array $cart, SalesChannelContext $context): bool
    {
        $debug = $this->systemConfigService->get('AbetaPunchOut.config.debug', $context->getSalesChannelId());

        try {
            $this->httpClient->request('POST', $postUrl, [
                'json' => $cart,
                'timeout' => 30,
                'max_redirects' => 10,
                'headers' => [
                    'cache-control' => 'no-cache',
                ],
            ])->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            if ($debug) {
                $this->logger->addEntry('Abeta punch out', $context->getContext(), $e, [
                    'errorMessage' => 'Unable to export cart',
                    'exportData' => $cart,
                ]);
            }

            return false;
        }

        if ($debug) {
            $this->logger->addEntry('Abeta punch out', $context->getContext(), null, [
                'SuccessMessage' => 'cart data is exported to abeta',
                'exportData' => $cart,
            ]);
        }

        return true;
    }
}
