<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsSeoInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TranslationInterface;

interface SharedBlockTranslationInterface extends TranslationInterface, ResourceInterface, \Stringable
{
}
