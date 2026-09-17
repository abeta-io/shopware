<?php
declare(strict_types=1);

namespace MagmodulesAbeta\Service;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class AbetaApiAuthenticator
{
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function assertValid(string $apiKey, SalesChannelContext $context): void
    {
        if (!$this->systemConfigService->get('MagmodulesAbeta.config.active', $context->getSalesChannelId())) {
            throw new \Exception('Abeta is not active.');
        }

        $configuredApiKey = $this->systemConfigService->get('MagmodulesAbeta.config.abetaApi', $context->getSalesChannelId());

        if ($configuredApiKey === null) {
            throw new \Exception('Abeta API key not configured.');
        }

        if (!hash_equals($configuredApiKey, $apiKey)) {
            throw new \Exception('Abeta API key not valid.');
        }
    }
}
