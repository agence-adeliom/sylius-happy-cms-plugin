<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Seo\Sitemap;

use Adeliom\SyliusEasyCrudPlugin\Traits\EntityTimestampableTrait;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

abstract class AbstractSitemapDumper implements SitemapDumperInterface
{
    abstract public static function getSitemapSection(): string;

    public static function getSitemapRoute(): string
    {
        return 'cmf_routing_object';
    }

    /**
     * @return array{
     *     _route_object: ?RouteObjectInterface
     * }
     */
    public static function getSitemapRouteParams(CmsRoutableInterface $entity): array
    {
        return [
            '_route_object' => $entity->getOnlineRoute(),
        ];
    }

    abstract public function getEntities(): array;

    public function getLastModifiedDate(CmsRoutableInterface $entity): ?\DateTimeInterface
    {
        if (class_implements($entity) && in_array(EntityTimestampableTrait::class, class_implements($entity)) && method_exists($entity, 'getUpdatedAt') && is_object($entity)) {
            return $entity->getUpdatedAt();
        }

        return null;
    }

    public function replaceUrl(string $url, CmsRoutableInterface $entity): ?string
    {
        return null;
    }
}
