<?php
declare(strict_types=1);

namespace Abeta\PunchOut\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1711120902RemoveUsernamePasswordColumns extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1711120902;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `abeta_customer_login` DROP COLUMN user_name;');
        $connection->executeStatement('ALTER TABLE `abeta_customer_login` DROP COLUMN user_password;');
    }
}
