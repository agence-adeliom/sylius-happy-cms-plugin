<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Media;
use Adeliom\SyliusHappyCMSPlugin\Repository\Media\MediaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\Table(name: 'referenced_media')]
class ReferencedMedia extends Media
{
}
