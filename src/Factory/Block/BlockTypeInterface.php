<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\Block;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Sylius\Resource\Model\ResourceInterface;

interface BlockTypeInterface
{
    public function getName(): string;

    /**
     * @return string|mixed[]
     */
    public function getIcon(): string|array;

    public function getPosition(): int;

    public function getFrontEndTemplatePath(): string;

    /**
     * Declare here the assets that make front working as expected
     *
     * @return array{js?: array<string|Asset>|null, css?: array<string|Asset>|null, webpack?: array<string|Asset>|null}
     */
    public function configureAssets(): array;

    /**
     * Declare here the assets that make back-office working as expected
     *
     * @return array{js?: array<string|Asset>|null, css?: array<string|Asset>|null, webpack?: array<string|Asset>|null}
     */
    public function configureAdminAssets(): array;

    /**
     * Declare here the form themes path that make back-office form display as expected
     *
     * @return string[]
     */
    public function configureAdminFormThemes(): array;

    /**
     * @return string[]
     */
    public static function researchableProperties(): array;

    public function supports(?ResourceInterface $resource = null): bool;
}
