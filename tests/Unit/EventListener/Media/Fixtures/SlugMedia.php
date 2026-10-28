<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Media\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Media;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'slug_media')]
class SlugMedia extends Media
{
}
