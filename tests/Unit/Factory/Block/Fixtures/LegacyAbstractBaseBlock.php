<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;

/**
 * A project base class written for 2.3.x: it redeclares the metadata methods abstract.
 */
abstract class LegacyAbstractBaseBlock extends AbstractBlock
{
    abstract public function getName(): string;

    abstract public function getIcon(): string;

    abstract public function getFrontEndTemplatePath(): string;
}
