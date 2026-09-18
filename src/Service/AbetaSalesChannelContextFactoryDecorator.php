<?php
declare(strict_types=1);

namespace Abeta\PunchOut\Service;

use Abeta\PunchOut\Struct\AbetaSession;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AbetaSalesChannelContextFactoryDecorator extends AbstractSalesChannelContextFactory
{
    public function __construct(
        private readonly AbstractSalesChannelContextFactory $decorated,
    ) {
    }

    public function getDecorated(): AbstractSalesChannelContextFactory
    {
        return $this->decorated;
    }

    public function create(string $token, string $salesChannelId, array $options = []): SalesChannelContext
    {
        $context = $this->decorated->create($token, $salesChannelId, $options);

        if (empty($options['abetaSessionId'])) {
            return $context;
        }

        $context->addExtension(
            AbetaSession::EXTENSION_NAME,
            new AbetaSession(
                (string)$options['abetaSessionId'],
                isset($options['abetaReturnUrl']) ? (string)$options['abetaReturnUrl'] : null,
                isset($options['abetaToken']) ? (string)$options['abetaToken'] : null,
            )
        );

        return $context;
    }
}
