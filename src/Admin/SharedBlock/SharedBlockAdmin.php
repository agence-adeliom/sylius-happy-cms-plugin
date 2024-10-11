<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlock;

class SharedBlockAdmin extends AbstractSharedBlockAdmin implements SharedBlockAdminInterface
{
    public static function getName(): string
    {
        return 'sylius_happy_cms_shared_block_admin';
    }

    public static function getEntityFqcn(): string
    {
        return SharedBlock::class;
    }
}
