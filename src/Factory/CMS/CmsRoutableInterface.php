<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\CMS;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelAwareInterface;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface CmsRoutableInterface extends ChannelAwareInterface
{
    /**
     * @return Collection|TranslationInterface[]
     *
     * @psalm-return Collection<array-key, TranslationInterface>
     */
    public function getTranslations(): Collection;

    public function isOnline(): bool;

    /**
     * @return array{label: string, route: RouteObjectInterface}
     */
    public function getBreadcrumbItems(): array;

    public function previewIsAvailable(): bool;

    public function isStatePublished(): bool;

    public function isStateUnpublished(): bool;

    public function isStatePending(): bool;

    public function hasState(?string $state): bool;

    public function isDatePublished(): bool;

    public function getRouteUnikName(): string;

    public function renderResponse(Request $request, Response $response, RouteInterface $route, bool $cacheEnabled): Response;

    public function isHttpCacheEnabled(string $env, RouteInterface $route): bool;

    /**
     * @return string[]
     */
    public function getRouteMethods(): array;

    /**
     * @return array<string, mixed>
     */
    public function getRouteOptions(TranslationInterface $translation): array;

    /**
     * @return array<string, mixed>
     */
    public function getRouteRequirements(TranslationInterface $translation): array;

    /**
     * @return array<string, mixed>
     */
    public function getRouteDefaults(TranslationInterface $translation): array;

    /**
     * @return string[]
     */
    public function getRouteSchemes(TranslationInterface $translation): array;

    public function getRouteHost(TranslationInterface $translation): ?string;

    public function getRouteStaticPrefix(TranslationInterface $translation, bool $isPreview): string;

    public function getVariablePattern(TranslationInterface $translation, bool $isPreview): string;

    public function getRouteTemplate(): ?string;

    public function getRouteController(): ?string;

    public function getOnlineRoute(): ?RouteObjectInterface;

    public function getPreviewRoute(): ?RouteObjectInterface;

    public function getTranslation(?string $locale = null): TranslationInterface;

    /**
     * @return Collection<int, RouteInterface>
     */
    public function getRoutes(): Collection;

    /**
     * Add a route to the collection.
     */
    public function addRoute(RouteInterface $route): void;

    /**
     * Remove a route from the collection.
     */
    public function removeRoute(RouteInterface $route): void;
}
