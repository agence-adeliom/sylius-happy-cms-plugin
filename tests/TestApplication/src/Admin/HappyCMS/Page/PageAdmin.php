<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Admin\HappyCMS\Page;

use Adeliom\SyliusHappyCMSPlugin\Admin\Page\PageAdmin as BasePageAdmin;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\Page;

class PageAdmin extends BasePageAdmin
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_page_admin';
    }

    public static function getEntityFqcn(): string
    {
        return Page::class;
    }
}
