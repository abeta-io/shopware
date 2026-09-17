<?php
declare(strict_types=1);

namespace MagmodulesAbeta\Controller;

use MagmodulesAbeta\Service\AbetaApiAuthenticator;
use MagmodulesAbeta\Service\AbetaCartService;
use MagmodulesAbeta\Service\AbetaContextService;
use MagmodulesAbeta\Service\AbetaItemDataService;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class AbetaItemDataController extends StorefrontController
{
    public function __construct(
        private readonly AbetaApiAuthenticator $apiAuthenticator,
        private readonly AbetaContextService $contextService,
        private readonly AbetaItemDataService $itemDataService,
        private readonly AbetaCartService $abetaCartService,
        private readonly CartService $cartService,
    ) {
    }

    #[Route(path: '/v1/abeta/itemdata', name: 'frontend.v1.abeta.item-data', methods: ['POST'])]
    public function itemData(Request $request, SalesChannelContext $context): Response
    {
        $postData = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        try {
            $this->validateData($postData, $context);

            $customer = $this->itemDataService->findCustomerByEmail(trim((string) $postData['email']), $context);

            if (!$customer) {
                throw new \Exception('Customer not found.');
            }
        } catch (\Throwable $t) {
            return new JsonResponse([
                'type' => 'error',
                'message' => $t->getMessage(),
            ]);
        }

        $isolatedContext = $this->contextService->createIsolatedCustomerContext($customer->getId(), $context);

        try {
            $result = $this->itemDataService->addProductsToCart($postData['products'], $isolatedContext);

            if ($result['cart']->getLineItems()->count() === 0) {
                return new JsonResponse([
                    'type' => 'error',
                    'message' => 'No valid products found for the given SKUs.',
                    'not_found_skus' => $result['notFoundSkus'],
                ]);
            }

            $cartData = $this->abetaCartService->getCartExportData($isolatedContext);
            $cartData['not_found_skus'] = $result['notFoundSkus'];

            return new JsonResponse(['type' => 'success'] + $cartData);
        } catch (\Throwable) {
            return new JsonResponse([
                'type' => 'error',
                'message' => 'Unable to process item data request.',
            ]);
        } finally {
            // Each cleanup call is isolated so a failure in one can't skip the other, and
            // neither can override an already-computed response above (a finally block that
            // throws replaces the try block's return value with that exception in PHP).
            try {
                $this->cartService->deleteCart($isolatedContext);
            } catch (\Throwable) {
            }

            try {
                $this->contextService->destroyIsolatedContext($isolatedContext);
            } catch (\Throwable) {
            }
        }
    }

    private function validateData(array $data, SalesChannelContext $context): void
    {
        foreach (['api_key', 'email', 'products'] as $requiredField) {
            if (empty($data[$requiredField])) {
                throw new \Exception(sprintf('%s not set or empty', $requiredField));
            }
        }

        if (!\is_array($data['products'])) {
            throw new \Exception('products must be an array.');
        }

        foreach ($data['products'] as $product) {
            if (!isset($product['sku']) || !\is_string($product['sku']) || $product['sku'] === '') {
                throw new \Exception('Each product requires a non-empty sku.');
            }

            if (!isset($product['qty']) || !is_numeric($product['qty']) || (int) $product['qty'] < 1) {
                throw new \Exception('Each product requires a qty of at least 1.');
            }
        }

        $this->apiAuthenticator->assertValid($data['api_key'], $context);
    }
}
