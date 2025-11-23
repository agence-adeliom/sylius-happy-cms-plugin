<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config;

use Adeliom\SyliusHappyCMSPlugin\Entity\Config\Config as BaseConfig;
use Adeliom\SyliusHappyCMSPlugin\Entity\Config\ConfigTranslationInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Repository\HappyCMS\Config\ConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigRepository::class)]
#[ORM\Table(name: 'sylius_happy_cms__config')]
class Config extends BaseConfig
{
    protected function createTranslation(): ConfigTranslationInterface
    {
        return new ConfigTranslation();
    }

    public static function getTranslationClass(): string
    {
        return ConfigTranslation::class;
    }
}
