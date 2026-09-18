<?php
declare(strict_types=1);

namespace Abeta\PunchOut\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItemFactoryRegistry;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AbetaItemDataService
{
    public function __construct(
        private readonly EntityRepository $customerRepository,
        private readonly SalesChannelRepository $productRepository,
        private readonly LineItemFactoryRegistry $lineItemFactoryRegistry,
        private readonly CartService $cartService,
    ) {
    }

    public function findCustomerByEmail(string $email, SalesChannelContext $context): ?CustomerEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('guest', false));
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('boundSalesChannelId', null),
            new EqualsFilter('boundSalesChannelId', $context->getSalesChannelId()),
        ]));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(1);

        /** @var CustomerEntity|null $customer */
        $customer = $this->customerRepository->search($criteria, $context->getContext())->first();

        return $customer;
    }

    /**
     * @param array<array{sku: string, qty: int}> $products
     *
     * @return array{cart: Cart, notFoundSkus: string[]}
     */
    public function addProductsToCart(array $products, SalesChannelContext $context): array
    {
        $skus = array_column($products, 'sku');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('productNumber', $skus));

        $productIdsBySku = [];
        foreach ($this->productRepository->search($criteria, $context) as $product) {
            $productIdsBySku[$product->getProductNumber()] = $product->getId();
        }

        $notFoundSkus = [];
        $lineItems = [];
        foreach ($products as $product) {
            $productId = $productIdsBySku[$product['sku']] ?? null;

            if ($productId === null) {
                $notFoundSkus[] = $product['sku'];

                continue;
            }

            $lineItems[] = $this->lineItemFactoryRegistry->create([
                'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                'referencedId' => $productId,
                'quantity' => (int) $product['qty'],
            ], $context);
        }

        $cart = $this->cartService->getCart($context->getToken(), $context);

        if ($lineItems !== []) {
            $cart = $this->cartService->add($cart, $lineItems, $context);
        }

        return ['cart' => $cart, 'notFoundSkus' => $notFoundSkus];
    }
}
