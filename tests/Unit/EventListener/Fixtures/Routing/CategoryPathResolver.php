<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RouteParentPathResolverInterface;
use Sylius\Resource\Model\TranslationInterface;

/**
 * Places a page under its category (a parent of another type, here a plain string). With $fixedPath, supports
 * nothing (used to check the resolver order).
 */
final class CategoryPathResolver implements RouteParentPathResolverInterface
{
    public function __construct(
        private readonly ?string $fixedPath = null,
    ) {
    }

    public function supports(CmsRoutableInterface $entity, TranslationInterface $translation): bool
    {
        return null === $this->fixedPath || '/too-late' === $this->fixedPath;
    }

    public function resolveParentPath(CmsRoutableInterface $entity, TranslationInterface $translation): string
    {
        if (null !== $this->fixedPath) {
            return $this->fixedPath;
        }

        return $entity instanceof IndexedPage && null !== $entity->category ? '/' . $entity->category : '';
    }
}
