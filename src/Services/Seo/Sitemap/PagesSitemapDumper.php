<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Seo\Sitemap;

use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Doctrine\ORM\EntityManagerInterface;

class PagesSitemapDumper extends AbstractSitemapDumper
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
    ) {
    }

    public static function getSitemapSection(): string
    {
        return 'pages';
    }

    /**
     * @return array<int, PageInterface|object>
     */
    public function getEntities(): array
    {
        return $this->manager->getRepository(PageInterface::class)->findAll();
    }
}
