<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock;

use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslationInterface;

interface SharedBlockTranslationInterface extends TranslationInterface, ResourceInterface, \Stringable
{
    public function getContent(): ?array;
}
