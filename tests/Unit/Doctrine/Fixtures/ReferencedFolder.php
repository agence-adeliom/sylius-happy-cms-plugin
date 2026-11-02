<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Folder;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'referenced_folder')]
class ReferencedFolder extends Folder
{
}
