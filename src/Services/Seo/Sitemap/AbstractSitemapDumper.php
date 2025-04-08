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
     * @return array<mixed>
     */
    public static function getSitemapRouteParams(mixed $entity): array
    {
        return [
            '_route_object' => $entity->getOnlineRoute(),
        ];
    }

    abstract public function getEntities(): array;

    public function getLastModifiedDate(mixed $entity): ?\DateTimeInterface
    {
        if (in_array(EntityTimestampableTrait::class, class_implements($entity)) && method_exists($entity, 'getUpdatedAt')) {
            return $entity->getUpdatedAt();
        }

        return null;
    }

    public function replaceUrl(string $url, mixed $entity): ?string
    {
        return null;
    }
}
