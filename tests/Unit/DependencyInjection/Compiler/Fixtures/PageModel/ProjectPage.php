<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler\Fixtures\PageModel;

use Adeliom\SyliusHappyCMSPlugin\Entity\Page\Page as BasePage;
use Doctrine\ORM\Mapping as ORM;

/**
 * Project page extending the plugin page, without the `contentBlocks` association of the relational page builder.
 */
#[ORM\Entity]
#[ORM\Table(name: 'model_page')]
class ProjectPage extends BasePage
{
}
