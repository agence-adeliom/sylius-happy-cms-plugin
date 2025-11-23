<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DataCollector\Block;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\Helper;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class BlockCollector extends AbstractDataCollector
{
    public function __construct(protected Helper $blockHelper)
    {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data['blocks'] = $this->blockHelper->getTraces();
    }

    /**
     * @return BlockTypeInterface[]
     */
    public function getBlocks(): array
    {
        return $this->data['blocks'] ?: [];
    }

    public function getName(): string
    {
        return self::class;
    }
}
