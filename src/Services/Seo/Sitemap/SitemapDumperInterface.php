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

    /** @return array{
     *  _route_object: ?RouteObjectInterface
     * } */
    public static function getSitemapRouteParams(CmsRoutableInterface $entity): array;

    /** @return array<mixed> */
    public function getEntities(): array;

    public function getLastModifiedDate(CmsRoutableInterface $entity): ?\DateTimeInterface;

    public function replaceUrl(string $url, CmsRoutableInterface $entity): ?string;
}
