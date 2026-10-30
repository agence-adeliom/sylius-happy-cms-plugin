<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Config;

use Adeliom\SyliusHappyCMSPlugin\Enum\Config\ConfigTypeEnum;

/**
 * Convertit la valeur brute (texte) d'une config selon son type.
 * Utilisé pour la valeur par défaut comme pour les surcharges par channel.
 */
final class ConfigValueConverter
{
    public function convert(string $type, mixed $rawValue): mixed
    {
        return match ($type) {
            ConfigTypeEnum::BOOLEAN => (bool) $rawValue,
            ConfigTypeEnum::DATE, ConfigTypeEnum::TIME, ConfigTypeEnum::DATETIME => $this->toDateTime($rawValue),
            default => $rawValue,
        };
    }

    private function toDateTime(mixed $rawValue): ?\DateTime
    {
        if (!is_string($rawValue)) {
            return null;
        }

        try {
            return new \DateTime($rawValue);
        } catch (\Exception) {
            return null;
        }
    }
}
