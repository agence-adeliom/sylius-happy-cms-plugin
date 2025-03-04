<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\CMS;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Route as OrmRoute;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

interface CmsRoutableInterface
{
    /**
     * @return Collection|TranslationInterface[]
     *
     * @psalm-return Collection<array-key, TranslationInterface>
     */
    public function getTranslations(): Collection;

    public function isOnline(): bool;

    public function previewIsAvailable(): bool;

    public function isStatePublished(): bool;

    public function isStateUnpublished(): bool;

    public function isStatePending(): bool;

    public function hasState(?string $state): bool;

    public function isDatePublished(): bool;

    public function getRouteUnikName(): string;

    public function getChannel(): ?ChannelInterface;

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
     * @return Collection<int, OrmRoute>
     */
    public function getRoutes(): Collection;

    /**
     * Add a route to the collection.
     */
    public function addRoute(OrmRoute $route): void;

    /**
     * Remove a route from the collection.
     */
    public function removeRoute(OrmRoute $route): void;
}
