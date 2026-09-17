<?php
declare(strict_types=1);

namespace MagmodulesAbeta;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class MagmodulesAbeta extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }
        /**
         * @var Connection $connection
         */
        $connection = $this->container->get(Connection::class);
        try {
            $connection->executeStatement(
                'DELETE FROM system_config WHERE configuration_key LIKE :domain',
                [
                    'domain' => '%MagmodulesAbeta.config.%',
                ]
            );
            $connection->executeStatement('DROP TABLE IF EXISTS `abeta_customer_login`');
        } catch (Exception) {
        }
        // Remove or deactivate the data created by the plugin
    }
}
