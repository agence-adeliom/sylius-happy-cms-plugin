<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Page;

use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;

class PageAdmin extends AbstractPageAdmin
{
    public static function getName(): string
    {
        return 'sylius_happy_cms_page_admin';
    }

    public static function getEntityFqcn(): string
    {
        return PageInterface::class;
    }
}
