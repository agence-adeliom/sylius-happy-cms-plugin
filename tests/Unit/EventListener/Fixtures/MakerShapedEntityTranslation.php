<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures;

use Sylius\Resource\Model\AbstractTranslation;

class MakerShapedEntityTranslation extends AbstractTranslation
{
    private ?string $slug = null;

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }
}
