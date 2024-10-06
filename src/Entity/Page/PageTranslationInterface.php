<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Page;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsSeoInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TranslationInterface;

interface PageTranslationInterface extends TranslationInterface, ResourceInterface, CmsSeoInterface, \Stringable
{
    public function getSlug(): ?string;

    public function getName(): ?string;
}
