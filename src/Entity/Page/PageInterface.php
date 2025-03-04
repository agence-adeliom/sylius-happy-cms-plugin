<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Page;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Doctrine\Common\Collections\Collection;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;

interface PageInterface extends ResourceInterface, TranslatableInterface, CmsRoutableInterface
{
    public const HOMEPAGE = 'homepage';

    public function getPublishState(): ?string;

    public function getSlug(): ?string;

    public function getTranslation(?string $locale = null): PageTranslationInterface;

    public function getParent(): ?self;

    public function setParent(?self $parent = null): void;

    /**
     * @return Collection<int, PageInterface>
     */
    public function getChildren(): Collection;

    public function addChildren(self $page): void;

    /**
     * @return Collection<int, PageTranslationInterface>
     */
    public function getSlugTranslations(): Collection;

    /**
     * @return Collection<int, PageTranslationInterface>
     */
    public function getSeoTranslations(): Collection;

    public function getLft(): ?int;

    public function getRgt(): ?int;

    public function getRoot(): ?int;

    public function getLvl(): ?int;

    public function getPosition(): ?int;

    public function setPosition(int $position): void;
}
