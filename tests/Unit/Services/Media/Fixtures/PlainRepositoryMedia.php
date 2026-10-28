<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Media;
use Doctrine\ORM\Mapping as ORM;

/**
 * A media entity of an application: default Doctrine repository (or its own ServiceEntityRepository),
 * which does not implement MediaRepositoryInterface.
 */
#[ORM\Entity]
#[ORM\Table(name: 'plain_repository_media')]
class PlainRepositoryMedia extends Media
{
}
