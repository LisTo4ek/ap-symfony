<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Trait;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

trait DatabaseSetupTrait
{
    protected EntityManagerInterface $entityManager;
    private static bool $schemaCreated = false;

    protected function setUpDatabase(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $forceRecreate = filter_var(
            $_ENV['TEST_DB_FORCE_RECREATE'] ?? $_SERVER['TEST_DB_FORCE_RECREATE'] ?? 'false',
            FILTER_VALIDATE_BOOLEAN
        );
        $schemaNeedsCreate = filter_var(
            $_ENV['TEST_DB_SCHEMA_NEEDS_CREATE'] ?? $_SERVER['TEST_DB_SCHEMA_NEEDS_CREATE'] ?? 'false',
            FILTER_VALIDATE_BOOLEAN
        );
        if (!self::$schemaCreated || $forceRecreate || $schemaNeedsCreate) {
            $this->createSchema();
            self::$schemaCreated = true;
            $_ENV['TEST_DB_SCHEMA_NEEDS_CREATE'] = 'false';
            $_SERVER['TEST_DB_SCHEMA_NEEDS_CREATE'] = 'false';
        } else {
            $this->clearTables();
        }
    }

    protected function tearDownDatabase(): void
    {
        if (isset($this->entityManager) && $this->entityManager->isOpen()) {
            $this->entityManager->clear();
        }
    }

    private function createSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function clearTables(): void
    {
        $connection = $this->entityManager->getConnection();
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $connection->executeStatement('SET session_replication_role = replica');
        foreach ($metadata as $classMetadata) {
            $tableName = $classMetadata->getTableName();
            $connection->executeStatement(sprintf('TRUNCATE TABLE "%s" CASCADE', $tableName));
        }
        $connection->executeStatement('SET session_replication_role = DEFAULT');
    }

    protected function clearEntityManager(): void
    {
        $this->entityManager->clear();
    }
}
