<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Sylius\Resource\Model\TranslationInterface;

/**
 * Computes the path a CMS route is placed under when it depends on a parent of another type (a taxon, a
 * non-routable category, a page of another class…): the {{other_entity_path}} placeholder of the URL pattern.
 *
 * Implementations are autoconfigured with the tag below; the first one supporting the entity is used (tag priority
 * order). Listeners of CalculateRouteStaticPrefixEvent receive its result and can still change it.
 */
interface RouteParentPathResolverInterface
{
    public const TAG = 'sylius_happy_cms.route_parent_path_resolver';

    public function supports(CmsRoutableInterface $entity, TranslationInterface $translation): bool;

    /**
     * @return string the parent path, starting with "/" (e.g. "/doors/wooden"), or "" when there is none
     */
    public function resolveParentPath(CmsRoutableInterface $entity, TranslationInterface $translation): string;
}
