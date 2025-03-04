<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Field\Configurator;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\SharedBlockField;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockTypeInterface;
use Sylius\Resource\Model\ResourceInterface;

/**
 * Inspired by EasyAdmin Symfony Bundle
 */
final class SharedBlockConfigurator implements FieldConfiguratorInterface
{
    public function __construct(protected SharedBlockCollection $collection)
    {
    }

    public function supports(FieldDto $field, ?ResourceInterface $resource = null): bool
    {
        return SharedBlockField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, ?ResourceInterface $resource = null): void
    {
        $blocksCollection = $this->collection->enabledSupportFilter();
        $blocks = $blocksCollection->getBlocks();

        foreach ($blocks as $blockType => $block) {
            if ($blockType === $field->getFormType()) {
                if ($block instanceof SharedBlockTypeInterface && method_exists($blockType, 'configureAdminAssets')) {
                    $field->addAssets($block->configureAdminAssets());
                }
                if ($block instanceof SharedBlockTypeInterface && method_exists($blockType, 'configureAdminFormThemes')) {
                    $field->addFormThemes($block->configureAdminFormThemes());
                }
            }
        }
    }

    public function formatValue(FieldDto $field, mixed $value): mixed
    {
        return $value;
    }
}
