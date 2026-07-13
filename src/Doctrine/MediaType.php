<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Doctrine;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

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
     * @return array<int, string|null>
     */
    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return [MediaInterface::class, null];
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
     * Sans ce commentaire SQL, Doctrine\DBAL\Schema\Comparator::diffColumn() compare les types par
     * get_class() : l'introspection de la base reconstruit un BigIntType natif au lieu de MediaType
     * (la SQL déclaration des deux est identique, BIGINT), ce qui génère un diff de schéma fantôme
     * (ALTER ... TYPE BIGINT) à chaque doctrine:schema:validate, quelle que soit la migration appliquée.
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
