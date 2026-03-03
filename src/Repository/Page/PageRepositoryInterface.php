<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Page;

use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

interface PageRepositoryInterface
{
    public function findPreviousPage(PageInterface $page): ?PageInterface;

    public function findNextPage(PageInterface $page): ?PageInterface;

    /**
     * @return PageInterface[]
     */
    public function getBySlug(string $slug, string $locale): array;

    public function getByTemplate(string $template, string $locale, ChannelInterface $channel): ?PageInterface;

    public function getHomePage(string $locale, ?ChannelInterface $channel = null): ?PageInterface;

    public function getBySeoKey(string $seoKey, string $locale): ?PageInterface;
}
