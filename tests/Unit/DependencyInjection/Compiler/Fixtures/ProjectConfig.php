<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Entity\Config\Config;
use Doctrine\ORM\Mapping as ORM;

/** A project config model extending the plugin one, as in Tryba. */
#[ORM\Entity(repositoryClass: ProjectConfigRepository::class)]
class ProjectConfig extends Config
{
}
