<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Event\PageBuilder;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched by the page builder once the content of an entity is published and saved (draft data copied to the
 * published data of its blocks, deleted blocks removed). A project can purge the HTTP cache of the entity's page or
 * reindex it.
 */
final class ContentPublishedEvent extends Event
{
    /**
     * @param list<string> $locales the published locales
     */
    public function __construct(
        private readonly ContentEditableInterface $entity,
        private readonly array $locales,
    ) {
    }

    public function getEntity(): ContentEditableInterface
    {
        return $this->entity;
    }

    /**
     * @return list<string>
     */
    public function getLocales(): array
    {
        return $this->locales;
    }
}
