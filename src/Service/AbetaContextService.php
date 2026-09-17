<?php
declare(strict_types=1);

namespace MagmodulesAbeta\Service;

use Shopware\Core\Framework\Util\Hasher;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AbetaContextService
{
    public function __construct(
        private readonly SalesChannelContextServiceInterface $salesChannelContextService,
        private readonly SalesChannelContextPersister $contextPersister,
    ) {
    }

    public function buildPunchoutToken(string $customerId, ?string $sessionId): string
    {
        return $sessionId !== null
            ? Hasher::hash($customerId . '_' . $sessionId, 'md5')
            : Random::getAlphanumericString(32);
    }

    public function createIsolatedCustomerContext(
        string $customerId,
        SalesChannelContext $context,
        ?string $sessionId = null
    ): SalesChannelContext {
        $token = $this->buildPunchoutToken($customerId, $sessionId);

        $payload = [
            'customerId' => $customerId,
            'permissions' => [],
        ];

        if ($sessionId !== null) {
            $payload['abetaSessionId'] = $sessionId;
        }

        $this->contextPersister->save(
            $token,
            $payload,
            $context->getSalesChannelId()
        );

        return $this->salesChannelContextService->get(
            new SalesChannelContextServiceParameters(
                $context->getSalesChannelId(),
                $token,
                $context->getLanguageIdChain()[0],
                $context->getCurrencyId(),
                $context->getDomainId(),
                $context->getContext(),
                $customerId
            )
        );
    }

    public function destroyIsolatedContext(SalesChannelContext $context): void
    {
        $this->contextPersister->delete($context->getToken(), $context->getSalesChannelId());
    }
}
