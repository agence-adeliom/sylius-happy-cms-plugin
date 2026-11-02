<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Doctrine;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * DBAL type storing a reference to a Happy CMS media as its id (BIGINT), like happy_cms_media_type,
 * but reading it back as a MediaInterface instead of the raw id.
 *
 * - Write: same as MediaType (MediaInterface, int or numeric string → id).
 * - Read: a lazy reference (EntityManager::getReference()) to the configured media entity
 *   (sylius_happy_cms.media.media_entity). No SQL query is run until a field other than the id is read,
 *   so an entity with several media columns costs nothing for the columns a page does not display.
 *   A media already loaded in the unit of work is returned as is (identity map).
 * - NULL, 0, '' or a non-numeric value → null.
 * - Id of a media that no longer exists: the reference is still returned (checking it would need the
 *   query this type avoids). Reading one of its fields throws Doctrine\ORM\EntityNotFoundException;
 *   MediaHelper::getMedia() (and therefore the happy_cms_media* Twig functions and the media form type)
 *   returns null for it, like for an unknown id.
 *
 * Why a separate type rather than an option of happy_cms_media_type? DBAL calls convertToPHPValue()
 * without the column options, so a per-column "hydrate" flag cannot reach it: the type name is the switch.
 *
 * The reference factory is set by SyliusHappyCMSPlugin::boot(). DBAL types are instantiated by DBAL
 * itself, without the service container, hence the static setter.
 */
class MediaReferenceType extends MediaType
{
    /**
     * @var string
     */
    public const TYPE = 'happy_cms_media_reference_type';

    /** @var (\Closure(int): MediaInterface)|null */
    private static ?\Closure $referenceFactory = null;

    /**
     * @param (\Closure(int): MediaInterface)|null $referenceFactory returns a (lazy) media for an id
     */
    public static function setReferenceFactory(?\Closure $referenceFactory): void
    {
        self::$referenceFactory = $referenceFactory;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?MediaInterface
    {
        if (!is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        if (null === self::$referenceFactory) {
            throw new \LogicException(sprintf('The "%s" Doctrine type has no media reference factory: the SyliusHappyCMSPlugin bundle must be booted (or call %s::setReferenceFactory()).', self::TYPE, self::class));
        }

        return (self::$referenceFactory)((int) $value);
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
