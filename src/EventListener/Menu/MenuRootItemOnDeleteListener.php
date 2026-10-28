<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener\Menu;

use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ToOneOwningSideMapping;

/**
 * Maps the `root_item_id` key of the menus `ON DELETE SET NULL` instead of `CASCADE`
 * (`sylius_happy_cms.menu.root_item_on_delete_set_null: true`).
 *
 * The root item also references the menu (`MenuItem#menu`): with a cascading key on `Menu#rootItem`, the unit of work
 * of ORM 3 cannot order the deletion of a menu removed with its items (`CycleDetectedException`), and deleting the root
 * item deletes the menu row behind it. With `SET NULL` the items are deleted first, then the menu; deleting the root
 * item alone leaves the menu without root.
 */
final class MenuRootItemOnDeleteListener
{
    public const ON_DELETE = 'SET NULL';

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $metadata = $args->getClassMetadata();
        if (!is_a($metadata->getName(), MenuInterface::class, true) || !$metadata->hasAssociation('rootItem')) {
            return;
        }

        $mapping = $metadata->associationMappings['rootItem'];

        // doctrine/orm 2: the mapping is an array.
        /** @phpstan-ignore function.impossibleType */
        if (is_array($mapping)) {
            /** @phpstan-ignore assign.propertyType */
            $metadata->associationMappings['rootItem'] = $this->setNullOnDelete($mapping);

            return;
        }

        if (!$mapping instanceof ToOneOwningSideMapping) {
            return;
        }

        foreach ($mapping->joinColumns as $joinColumn) {
            $joinColumn->onDelete = self::ON_DELETE;
        }
    }

    /**
     * @param array<string, mixed> $mapping
     *
     * @return array<string, mixed>
     */
    private function setNullOnDelete(array $mapping): array
    {
        $joinColumns = is_array($mapping['joinColumns'] ?? null) ? $mapping['joinColumns'] : [];
        foreach ($joinColumns as $index => $joinColumn) {
            if (is_array($joinColumn)) {
                $joinColumn['onDelete'] = self::ON_DELETE;
                $joinColumns[$index] = $joinColumn;
            }
        }
        $mapping['joinColumns'] = $joinColumns;

        return $mapping;
    }
}
