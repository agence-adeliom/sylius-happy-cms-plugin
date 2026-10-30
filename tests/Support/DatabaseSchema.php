<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Support;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

final class DatabaseSchema
{
    /**
     * Drops then recreates the schema for every mapped entity.
     *
     * SchemaTool::createSchema() cannot be used as is on PostgreSQL: Sylius' PostgreSQLDefaultSchemaListener
     * registers every existing database schema as a namespace, so "public" gets a CREATE SCHEMA statement
     * that fails because it already exists. The target schema always exists here, so those statements are skipped.
     */
    public static function reset(EntityManagerInterface $entityManager): void
    {
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropSchema($metadata);

        $connection = $entityManager->getConnection();
        foreach ($schemaTool->getCreateSchemaSql($metadata) as $sql) {
            if (str_starts_with($sql, 'CREATE SCHEMA ')) {
                continue;
            }

            $connection->executeStatement($sql);
        }
    }
}
