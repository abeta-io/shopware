<?php
declare(strict_types=1);

namespace MagmodulesAbeta\Controller;

use MagmodulesAbeta\Core\Content\AbetaLogin\AbetaLoginEntity;
use MagmodulesAbeta\Service\AbetaContextService;
use Shopware\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\CartRestorer;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class FrontAccountLoginController extends StorefrontController
{
    public function __construct(
        private readonly EntityRepository $abetaCustomerRepository,
        private readonly AbetaContextService $contextService,
        private readonly CartRestorer $cartRestorer,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SalesChannelContextPersister $contextPersister,
    ) {
    }

    #[Route(path: '/abeta-login/{token}', name: 'frontend.abeta.login-as-customer.frontend-customer.auth', defaults: [], methods: ['GET'])]
    public function login(Request $request, SalesChannelContext $context): Response
    {
        $token = $request->get('token');

        if (!$token || !Uuid::isValid($token)) {
            $this->addFlash('error', 'Invalid Abeta token.');

            return $this->redirectToRoute('frontend.home.page');
        }

        $abetaLogin = $this->getCustomerData($token, $context->getContext());
        if (!$abetaLogin instanceof AbetaLoginEntity) {
            $this->addFlash('error', 'Expired token.');

            return $this->redirectToRoute('frontend.home.page');
        }

        $customer = $abetaLogin->getCustomer();
        if ($customer === null) {
            $this->addFlash('error', 'Invalid login.');

            return $this->redirectToRoute('frontend.home.page');
        }

        $this->abetaCustomerRepository->delete([['id' => $abetaLogin->getId()]], $context->getContext());

        $punchoutToken = $this->contextService->buildPunchoutToken($customer->getId(), $abetaLogin->getSessionId());
        $punchoutContext = $this->cartRestorer->restoreByToken($punchoutToken, $customer->getId(), $context);

        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $punchoutContext);

        $this->eventDispatcher->dispatch(
            new CustomerLoginEvent($punchoutContext, $customer, $punchoutContext->getToken())
        );

        $this->contextPersister->save(
            $punchoutToken,
            [
                'abetaSessionId' => $abetaLogin->getSessionId(),
                'abetaReturnUrl' => $abetaLogin->getReturnUrl(),
                'abetaToken' => $token,
            ],
            $context->getSalesChannelId()
        );

        $request->attributes->set('redirectTo', $request->get('redirectTo', 'frontend.home.page'));

        return $this->createActionResponse($request);
    }

    private function getCustomerData(string $token, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addAssociation('customer');
        $criteria->addAssociation('salesChannel');
        $criteria->addFilter(new EqualsFilter('token', $token));

        return $this->abetaCustomerRepository->search($criteria, $context)->first();
    }
}
