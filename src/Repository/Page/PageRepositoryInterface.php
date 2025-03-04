<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Page;

use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;

interface PageRepositoryInterface
{
    public function findPreviousPage(PageInterface $page): ?PageInterface;

    public function findNextPage(PageInterface $page): ?PageInterface;

    /**
     * @return PageInterface[]
     */
    public function getBySlug(string $slug, string $locale): array;
}
