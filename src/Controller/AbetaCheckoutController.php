<?php
declare(strict_types=1);

namespace MagmodulesAbeta\Controller;

use MagmodulesAbeta\Service\AbetaCartService;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ["_routeScope" => ["storefront"]])]
class AbetaCheckoutController extends StorefrontController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly AbetaCartService $abetaCartService
    ) {
    }

    #[Route(path: "/v1/abeta/checkout", name: "frontend.abeta.checkout", methods: ["POST"])]
    public function info(Request $request, SalesChannelContext $context): Response
    {
        $cartData = $this->abetaCartService->getCartData($request, $context);

        $returnUrl = $cartData['general']['return_url'];

        $result = $this->abetaCartService->exportCartData($returnUrl, $cartData, $context);

        if ($result) {
            $this->cartService->deleteCart($context);
        }

        return $this->redirect($returnUrl);
    }
}
