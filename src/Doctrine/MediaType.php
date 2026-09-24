<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Doctrine;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * DBAL type storing a reference to a Happy CMS media as its id (BIGINT).
 *
 * Why a custom type instead of a ManyToOne association to Media?
 * The field is used inside Embeddables (e.g. Seo::$cover, embedded in every routable entity).
 * Doctrine does not allow associations in an Embeddable, only scalar columns. So we only store
 * the media id, without a foreign key.
 *
 * - Write (convertToDatabaseValue): accepts a MediaInterface, a numeric string (value submitted
 *   by the form) or an int, and persists the id.
 * - Read: convertToPHPValue is not overridden, so the property holds the raw id, not a Media
 *   entity. The media is resolved at render time through the happy_cms_media() Twig helper,
 *   hence the MediaInterface|int|null property types.
 *
 * The type is registered as happy_cms_media_type in config/packages/doctrine.yaml.
 */
class MediaType extends Type
{
    /**
     * @var string
     */
    public const TYPE = 'happy_cms_media_type';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBigIntTypeDeclarationSQL($column);
    }

    /**
     * Native SQL types mapped to this type during database introspection (reverse engineering).
     * We claim none: a BIGINT column must remain a native BigIntType.
     *
     * DBAL 4: the return value is passed to strtolower() for every registered type as soon as the
     * platform is initialized. The former value [MediaInterface::class, null] therefore crashed
     * every command using the platform (migrations included) with
     * "strtolower(): Argument #1 must be of type string, null given".
     *
     * @return list<string>
     */
    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return [];
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value) {
            if ($value instanceof MediaInterface) {
                return $value->getId();
            }
            if (is_string($value)) {
                return (int) $value;
            }

            return $value;
        }

        return null;
    }

    public function getName(): string
    {
        return self::TYPE;
    }

    /**
     * DBAL 3 only: without this SQL comment (DC2Type:happy_cms_media_type), the comparator
     * compares types by class. Introspection rebuilds a native BigIntType instead of MediaType,
     * which produces a phantom schema diff (ALTER ... BIGINT) on every doctrine:schema:validate
     * or doctrine:migrations:diff.
     *
     * DBAL 4: the method no longer exists in Type and is never called. The comparator
     * (AbstractPlatform::columnsEqual()) now compares the generated SQL declarations: BIGINT on
     * both sides, so no diff. The method is kept for DBAL 3 compatibility.
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
