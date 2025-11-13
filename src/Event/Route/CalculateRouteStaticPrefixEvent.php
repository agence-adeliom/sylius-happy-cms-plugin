<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Event\Route;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Sylius\Component\Resource\Model\TranslationInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CalculateRouteStaticPrefixEvent extends Event
{
    protected string $routeStaticPrefix = '';

    public function __construct(
        protected CmsRoutableInterface $entity,
        protected TranslationInterface $translation,
        protected ?bool $isPreview = false,
    ) {
    }

    public function getEntity(): CmsRoutableInterface
    {
        return $this->entity;
    }

    public function getTranslation(): TranslationInterface
    {
        return $this->translation;
    }

    public function isPreview(): bool
    {
        return $this->isPreview ?: false;
    }

    public function getRouteStaticPrefix(): string
    {
        return $this->routeStaticPrefix;
    }

    public function setRouteStaticPrefix(string $routeStaticPrefix): void
    {
        $this->routeStaticPrefix = $routeStaticPrefix;
    }
}
