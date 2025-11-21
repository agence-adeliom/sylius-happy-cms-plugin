<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Event\Block;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockTypeInterface;
use Symfony\Contracts\EventDispatcher\Event;

class BlockRender extends Event
{
    /**
     * @param array<string, mixed> $data
     * @param array{js: array<string|Asset>|null, css: array<string|Asset>|null, webpack: array<string|Asset>|null} $assets
     */
    public function __construct(
        private BlockTypeInterface|SharedBlockTypeInterface $block,
        private array $data,
        private array $assets,
    ) {
    }

    public function getBlock(): BlockTypeInterface|SharedBlockTypeInterface
    {
        return $this->block;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array{js: array<string|Asset>|null, css: array<string|Asset>|null, webpack: array<string|Asset>|null}
     */
    public function getAssets(): array
    {
        return $this->assets;
    }

    public function setBlock(BlockTypeInterface|SharedBlockTypeInterface $block): void
    {
        $this->block = $block;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param array{js: array<string|Asset>|null, css: array<string|Asset>|null, webpack: array<string|Asset>|null} $assets
     */
    public function setAssets(array $assets): void
    {
        $this->assets = $assets;
    }
}
