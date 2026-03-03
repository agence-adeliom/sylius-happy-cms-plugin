<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Page;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsSeoInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslationInterface;

interface PageTranslationInterface extends TranslationInterface, ResourceInterface, CmsSeoInterface, \Stringable
{
    public function getSlug(): ?string;

    public function setSlug(?string $slug): void;

    public function getName(): ?string;

    public function setName(?string $name): void;
}
