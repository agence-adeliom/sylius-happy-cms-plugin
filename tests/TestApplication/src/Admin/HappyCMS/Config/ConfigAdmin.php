<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Admin\HappyCMS\Config;

use Adeliom\SyliusHappyCMSPlugin\Admin\Config\ConfigAdmin as BaseConfigAdmin;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config\Config;

class ConfigAdmin extends BaseConfigAdmin
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_config_admin';
    }

    public static function getEntityFqcn(): string
    {
        return Config::class;
    }
}
