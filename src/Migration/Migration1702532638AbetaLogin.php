<?php
declare(strict_types=1);

namespace Abeta\PunchOut\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1702532638AbetaLogin extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1702532638;
    }

    public function update(Connection $connection): void
    {
        // implement update
        $connection->executeQuery("
             CREATE TABLE IF NOT EXISTS `abeta_customer_login` (
            `id` BINARY(16) NOT NULL,
            `customer_id` BINARY(16) NOT NULL,
            `user_name` VARCHAR(255) NULL,
            `user_password` VARCHAR(255) NULL,
            `session_id` VARCHAR(255) NULL,
            `sales_channel_id` BINARY(16) NULL,
            `return_url` VARCHAR(255) NULL,
            `token` VARCHAR(255) NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`),
            KEY `fk.abeta_customer_login.customer_id` (`customer_id`),
            KEY `fk.abeta_customer_login.sales_channel_id` (`sales_channel_id`),
            CONSTRAINT `fk.abeta_customer_login.customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk.abeta_customer_login.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}
