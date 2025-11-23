<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DataCollector\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\Helper;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockTypeInterface;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SharedBlockCollector extends AbstractDataCollector
{
    public function __construct(protected Helper $blockHelper)
    {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data['blocks'] = $this->blockHelper->getTraces();
    }

    /**
     * @return array<string, SharedBlockTypeInterface>
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
