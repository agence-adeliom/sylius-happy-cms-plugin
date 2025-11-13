<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Media;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Folder as BaseFolder;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

#[Serializer\ExclusionPolicy('ALL')]
#[ORM\Entity]
#[ORM\Table(name: 'sylius_happy_cms__folder')]
class Folder extends BaseFolder
{
}
