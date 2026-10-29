<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Media\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Media;
use Doctrine\ORM\Mapping as ORM;

/**
 * A project entity that already sets its own slug length.
 */
#[ORM\Entity]
#[ORM\Table(name: 'overridden_slug_media')]
#[ORM\AttributeOverrides([
    new ORM\AttributeOverride(name: 'slug', column: new ORM\Column(length: 150)),
])]
class OverriddenSlugMedia extends Media
{
}
