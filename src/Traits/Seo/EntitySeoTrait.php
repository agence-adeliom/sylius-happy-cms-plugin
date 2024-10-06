<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Traits\Seo;

use Adeliom\SyliusHappyCMSPlugin\Entity\Seo\Seo;
use Adeliom\SyliusHappyCMSPlugin\Entity\Seo\SeoInterface;
use Doctrine\ORM\Mapping as ORM;

trait EntitySeoTrait
{
    #[ORM\Embedded(class: SeoInterface::class)]
    protected SeoInterface $seo;

    public function __construct()
    {
        $this->seo = new Seo();
    }

    public function setSeo(SeoInterface $seo): void
    {
        $this->seo = $seo;
    }

    public function getSeo(): Seo
    {
        return $this->seo;
    }
}
