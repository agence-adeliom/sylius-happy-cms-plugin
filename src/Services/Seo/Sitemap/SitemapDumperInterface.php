<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Seo\Sitemap;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Every class implementing this interface is automatically used in the
 * TaggedIterator of Adeliom\SyliusHappyCMSPlugin\EventListener\Seo\SitemapSubscriber::sitemapDumpables.
 */
interface SitemapDumperInterface
{
    public static function getSitemapSection(): string;

    public static function getSitemapRoute(): string;

    /** @return array<mixed> */
    public static function getSitemapRouteParams(mixed $entity): array;

    /** @return array<mixed> */
    public function getEntities(): array;

    public function getLastModifiedDate(mixed $entity): ?\DateTimeInterface;

    public function replaceUrl(string $url, mixed $entity): ?string;
}
