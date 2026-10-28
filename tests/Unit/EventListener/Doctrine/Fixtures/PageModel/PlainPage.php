<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Doctrine\Fixtures\PageModel;

use Adeliom\SyliusHappyCMSPlugin\Entity\Page\Page as BasePage;
use Doctrine\ORM\Mapping as ORM;

/**
 * Project page extending the plugin page without inheritance (the usual case).
 */
#[ORM\Entity]
#[ORM\Table(name: 'model_plain_page')]
class PlainPage extends BasePage
{
}
