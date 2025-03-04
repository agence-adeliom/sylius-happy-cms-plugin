<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Config;

use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslationInterface;

interface ConfigTranslationInterface extends TranslationInterface, ResourceInterface
{
    public function setDate(?\DateTime $date): void;
}
