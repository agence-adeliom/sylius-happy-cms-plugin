<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;

/**
 * A project base class written for 2.3.x: it redeclares the metadata methods abstract and adds its own description.
 */
abstract class LegacyAbstractBaseSharedBlock extends AbstractSharedBlockType
{
    abstract public function getName(): string;

    abstract public function getIcon(): string;

    abstract public function getFrontEndTemplatePath(): string;

    abstract public function getDescription(): string;
}
