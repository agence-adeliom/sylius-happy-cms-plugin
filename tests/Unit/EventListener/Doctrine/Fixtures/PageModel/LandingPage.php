<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Doctrine\Fixtures\PageModel;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class LandingPage extends InheritedPage
{
}
