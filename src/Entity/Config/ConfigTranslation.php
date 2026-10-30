<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Config;

use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Adeliom\SyliusHappyCMSPlugin\Enum\Config\ConfigTypeEnum;
use Adeliom\SyliusHappyCMSPlugin\Services\Config\ConfigValueConverter;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Sylius\Resource\Model\AbstractTranslation;

#[HasLifecycleCallbacks]
#[MappedSuperclass]
class ConfigTranslation extends AbstractTranslation implements ConfigTranslationInterface, \Stringable
{
    use EntityIdTrait;

    /**
     * Le préfixe contient un tiret : le PropertyAccessor camelise les underscores, un code de channel
     * comme « values » donnerait sinon `channel_values` => getChannelValues()/setChannelValues().
     */
    public const CHANNEL_FIELD_PREFIX = 'channel-';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private mixed $value = null;

    /**
     * Surcharges de la valeur par code de channel : {"code_channel": "valeur brute en chaîne"}.
     *
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'channel_values', type: Types::JSON, nullable: true)]
    private ?array $channelValues = null;

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /** @return array<string, string> */
    public function getChannelValues(): array
    {
        return $this->channelValues ?? [];
    }

    /** @param array<string, string> $values */
    public function setChannelValues(array $values): void
    {
        $this->channelValues = $values;
    }

    public function getChannelValue(string $channelCode): ?string
    {
        return $this->getChannelValues()[$channelCode] ?? null;
    }

    /**
     * Une valeur `null` ou vide supprime la surcharge du channel.
     */
    public function setChannelValue(string $channelCode, ?string $value): void
    {
        $values = $this->getChannelValues();

        if (null === $value || '' === $value) {
            unset($values[$channelCode]);
        } else {
            $values[$channelCode] = $value;
        }

        $this->channelValues = $values;
    }

    private function getType(): ?string
    {
        /** @var ConfigInterface $translatable */
        $translatable = $this->getTranslatable();
        if ($translatable->getType()) {
            return $translatable->getType();
        }

        return null;
    }

    /**
     * Les champs de valeur par channel du formulaire admin sont nommés `channel_<code>` : le
     * PropertyAccessor d'EasyCrud les lit et les écrit via ces accesseurs magiques.
     */
    public function __isset(string $name): bool
    {
        return null !== self::channelCodeFromFieldName($name);
    }

    public function __get(string $name): mixed
    {
        if (null !== $channelCode = self::channelCodeFromFieldName($name)) {
            return $this->getChannelValue($channelCode);
        }

        if ($this->getType() === $name) {
            switch ($name) {
                case ConfigTypeEnum::DATE():
                    return $this->getDate();
                case ConfigTypeEnum::TIME():
                    return $this->getTime();
                case ConfigTypeEnum::DATETIME():
                    return $this->getDatetime();
                case ConfigTypeEnum::BOOLEAN():
                    return $this->getBoolean();
                default:
                    return $this->value;
            }
        }

        return null;
    }

    public function __set(?string $name, mixed $value): void
    {
        if (null !== $name && null !== $channelCode = self::channelCodeFromFieldName($name)) {
            $this->setChannelValue($channelCode, $this->channelFieldValueToString($value));

            return;
        }

        if ($name === $this->getType()) {
            $this->value = $value;
        }
    }

    public function getBoolean(): ?bool
    {
        if (ConfigTypeEnum::BOOLEAN() == $this->getType()) {
            return (bool) $this->convertValue(ConfigTypeEnum::BOOLEAN);
        }

        return null;
    }

    public function setDate(?\DateTime $date): void
    {
        if (null === $date) {
            $this->value = null;

            return;
        }
        if (ConfigTypeEnum::DATE() == $this->getType()) {
            $this->value = $date->format('Y-m-d');
        }
    }

    public function getDate(): ?\DateTime
    {
        if (ConfigTypeEnum::DATE() == $this->getType()) {
            $date = $this->convertValue(ConfigTypeEnum::DATE);

            return $date instanceof \DateTime ? $date : null;
        }

        return null;
    }

    public function setTime(?\DateTime $date): void
    {
        if (null === $date) {
            $this->value = null;

            return;
        }
        if (ConfigTypeEnum::TIME() == $this->getType()) {
            $this->value = $date->format('H:i:s');
        }
    }

    public function getTime(): ?\DateTime
    {
        if (ConfigTypeEnum::TIME() == $this->getType()) {
            $date = $this->convertValue(ConfigTypeEnum::TIME);

            return $date instanceof \DateTime ? $date : null;
        }

        return null;
    }

    public function setDatetime(?\DateTime $date): void
    {
        if (ConfigTypeEnum::DATETIME() == $this->getType() && $date) {
            $this->value = $date->format('Y-m-d H:i:s');
        }
    }

    public function getDatetime(): ?\DateTime
    {
        if (ConfigTypeEnum::DATETIME() == $this->getType()) {
            $date = $this->convertValue(ConfigTypeEnum::DATETIME);

            return $date instanceof \DateTime ? $date : null;
        }

        return null;
    }

    /**
     * Valeur brute en chaîne, comme la valeur par défaut. EasyCrud transmet la donnée brute soumise
     * (déjà une chaîne `Y-m-d` pour le champ date en `single_text`) ; un \DateTimeInterface est formaté selon le type.
     */
    private function channelFieldValueToString(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(match ($this->getType()) {
                ConfigTypeEnum::DATE => 'Y-m-d',
                ConfigTypeEnum::TIME => 'H:i:s',
                default => 'Y-m-d H:i:s',
            });
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return is_scalar($value) ? (string) $value : null;
    }

    private static function channelCodeFromFieldName(string $name): ?string
    {
        if (!str_starts_with($name, self::CHANNEL_FIELD_PREFIX) || strlen($name) === strlen(self::CHANNEL_FIELD_PREFIX)) {
            return null;
        }

        return substr($name, strlen(self::CHANNEL_FIELD_PREFIX));
    }

    private function convertValue(string $type): mixed
    {
        return (new ConfigValueConverter())->convert($type, $this->value);
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
