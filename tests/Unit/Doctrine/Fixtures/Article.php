<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Doctrine\MediaReferenceType;
use Adeliom\SyliusHappyCMSPlugin\Doctrine\MediaType;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * An application entity with media columns: two read as lazy references, one with the id-only type.
 */
#[ORM\Entity]
#[ORM\Table(name: 'article')]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(type: MediaReferenceType::TYPE, nullable: true)]
    public ?MediaInterface $image = null;

    #[ORM\Column(type: MediaReferenceType::TYPE, nullable: true)]
    public ?MediaInterface $thumbnail = null;

    #[ORM\Column(type: MediaType::TYPE, nullable: true)]
    public MediaInterface|int|null $legacyImage = null;
}
