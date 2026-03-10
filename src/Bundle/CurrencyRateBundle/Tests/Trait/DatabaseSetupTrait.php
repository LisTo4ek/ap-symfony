<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Trait;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use function dump;

trait DatabaseSetupTrait
{
    protected EntityManagerInterface $entityManager;
    private static bool $schemaCreated = false;

    protected function setUpDatabase(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Create schema only once per test run, or if forced
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

            // Clear the flag after creating schema
            $_ENV['TEST_DB_SCHEMA_NEEDS_CREATE'] = 'false';
            $_SERVER['TEST_DB_SCHEMA_NEEDS_CREATE'] = 'false';
        } else {
            // Just clear tables for clean test state
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

        // Drop and recreate schema
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function clearTables(): void
    {
        $connection = $this->entityManager->getConnection();
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Disable foreign key checks for PostgreSQL
        $connection->executeStatement('SET session_replication_role = replica');

        foreach ($metadata as $classMetadata) {
            $tableName = $classMetadata->getTableName();
            $connection->executeStatement(sprintf('TRUNCATE TABLE "%s" CASCADE', $tableName));
        }

        // Re-enable foreign key checks
        $connection->executeStatement('SET session_replication_role = DEFAULT');
    }

    protected function clearEntityManager(): void
    {
        $this->entityManager->clear();
    }
}
