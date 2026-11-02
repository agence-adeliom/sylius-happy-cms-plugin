<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Folder;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'managed_folder')]
class ManagedFolder extends Folder
{
}
