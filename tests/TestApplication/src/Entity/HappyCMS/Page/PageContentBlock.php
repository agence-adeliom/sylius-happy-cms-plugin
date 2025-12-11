<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page;

use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageContentBlock as BasePageContentBlock;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_happy_cms__page_content_block')]
#[ORM\Index(columns: ['page_id', 'locale', 'position'], name: 'idx_page_locale_position')]
#[ORM\Index(columns: ['page_id', 'locale', 'published'], name: 'idx_page_locale_published')]
class PageContentBlock extends BasePageContentBlock
{
}
