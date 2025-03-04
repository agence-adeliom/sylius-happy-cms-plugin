<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Config;

use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Adeliom\SyliusHappyCMSPlugin\Enum\Config\ConfigTypeEnum;
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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
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

    public function __get(string $name): mixed
    {
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
        if ($name === $this->getType()) {
            $this->value = $value;
        }
    }

    public function getBoolean(): ?bool
    {
        if (ConfigTypeEnum::BOOLEAN() == $this->getType()) {
            return (bool) $this->value;
        }

        return null;
    }

    public function setDate(?\DateTime $date): void
    {
        if (ConfigTypeEnum::DATE() == $this->getType() && $date) {
            $this->value = $date->format('Y-m-d');
        }
    }

    public function getDate(): ?\DateTime
    {
        if (ConfigTypeEnum::DATE() == $this->getType()) {
            try {
                return new \DateTime($this->value);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }

    public function setTime(?\DateTime $date): void
    {
        if (ConfigTypeEnum::TIME() == $this->getType()) {
            $this->value = $date->format('H:i:s');
        }
    }

    public function getTime(): ?\DateTime
    {
        if (ConfigTypeEnum::TIME() == $this->getType()) {
            try {
                return new \DateTime($this->value);
            } catch (\Exception) {
                return null;
            }
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
            try {
                return new \DateTime($this->value);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
