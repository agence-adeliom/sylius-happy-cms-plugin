<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Doctrine\Fixtures\PageModel;

use Adeliom\SyliusHappyCMSPlugin\Entity\Page\Page as BasePage;
use Doctrine\ORM\Mapping as ORM;

/**
 * Project page extending the plugin page, root of a single table inheritance.
 */
#[ORM\Entity]
#[ORM\Table(name: 'model_page')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['landing' => LandingPage::class])]
abstract class InheritedPage extends BasePage
{
}
