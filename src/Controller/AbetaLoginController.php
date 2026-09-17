<?php
declare(strict_types=1);

namespace MagmodulesAbeta\Controller;

use MagmodulesAbeta\Service\AbetaApiAuthenticator;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

#[Route(defaults: ["_routeScope" => ["storefront"]])]
class AbetaLoginController extends StorefrontController
{
    private const REQUIRED_FIELDS
        = [
            'username',
            'password',
            'session_id',
            'api_key',
            'return_url',
        ];

    public function __construct(
        private readonly AbetaApiAuthenticator $apiAuthenticator,
        private readonly EntityRepository $abetaCustomerRepository,
        private readonly AccountService $accountService,
        private readonly RouterInterface $router,
    ) {
    }

    #[Route(path: "/v1/abeta/login", name: "frontend.v1.abeta.login", methods: ["POST"])]
    public function create(Request $request, SalesChannelContext $context): Response
    {
        $postData = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $postData = array_map('trim', $postData);

        try {
            $this->validateData($postData, $context);
        } catch (\Throwable $t) {
            return new JsonResponse([
                'type' => 'error',
                'message' => $t->getMessage(),
            ]);
        }

        try {
            $customer = $this->accountService->getCustomerByLogin(
                $postData['username'],
                $postData['password'],
                $context
            );
        } catch (\Exception) {
            return new JsonResponse([
                'type' => 'error',
                'message' => 'Customer does not exist',
            ]);
        }

        $token = Uuid::randomHex();

        $this->abetaCustomerRepository->create([
            [
                'customerId' => $customer->getId(),
                'sessionId' => $postData['session_id'],
                'token' => $token,
                'salesChannelId' => $context->getSalesChannelId(),
                'returnUrl' => $postData['return_url'],
            ],
        ], $context->getContext());

        $redirectUrl = $this->router->generate('frontend.abeta.login-as-customer.frontend-customer.auth', [
            'token' => $token,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json([
            'type' => 'success',
            'one_time_url' => $redirectUrl,
        ]);
    }

    private function validateData(array $data, SalesChannelContext $context): void
    {
        foreach (self::REQUIRED_FIELDS as $requiredField) {
            if (empty($data[$requiredField])) {
                throw new \Exception(sprintf('%s not set or empty', $requiredField));
            }
        }

        $this->apiAuthenticator->assertValid($data['api_key'], $context);
    }
}
