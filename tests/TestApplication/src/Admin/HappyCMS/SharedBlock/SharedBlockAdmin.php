<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Admin\HappyCMS\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Admin\SharedBlock\SharedBlockAdmin as BaseSharedBlockAdmin;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\SharedBlock\SharedBlock;

class SharedBlockAdmin extends BaseSharedBlockAdmin
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_shared_block_admin';
    }

    public static function getEntityFqcn(): string
    {
        return SharedBlock::class;
    }
}
