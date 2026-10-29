<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener\Media;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Folder;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Media;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Widens the slug of the media and folder models (and of the project entities extending them) from 100 to
 * 255 characters, the length of the name the slug is derived from: a longer file or folder name no longer overflows
 * the slug (data too long on MySQL in strict mode).
 *
 * Registered only when "sylius_happy_cms.media.long_slugs" is true, so the default mapping stays at 100. A slug
 * whose length a project already overrides (#[ORM\AttributeOverride]) to anything other than 100 is left as is.
 */
final class LongSlugsListener
{
    public const LEGACY_LENGTH = 100;

    public const LENGTH = 255;

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $metadata = $args->getClassMetadata();
        if (!is_a($metadata->getName(), Media::class, true) && !is_a($metadata->getName(), Folder::class, true)) {
            return;
        }

        if (!$metadata->hasField('slug')) {
            return;
        }

        $mapping = $metadata->getFieldMapping('slug');
        if (self::LEGACY_LENGTH === $mapping->length) {
            $mapping->length = self::LENGTH;
        }
    }
}
