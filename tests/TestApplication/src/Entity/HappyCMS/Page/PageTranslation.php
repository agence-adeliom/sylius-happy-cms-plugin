<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page;

use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageTranslation as BasePageTranslation;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_happy_cms__page_translation')]
class PageTranslation extends BasePageTranslation
{
}
