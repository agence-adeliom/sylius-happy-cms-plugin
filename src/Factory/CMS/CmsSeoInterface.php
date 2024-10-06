<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\CMS;

use Adeliom\SyliusHappyCMSPlugin\Entity\Seo\SeoInterface;

interface CmsSeoInterface
{
    public function setSEO(SeoInterface $seo): void;

    public function getSEO(): SeoInterface;
}
