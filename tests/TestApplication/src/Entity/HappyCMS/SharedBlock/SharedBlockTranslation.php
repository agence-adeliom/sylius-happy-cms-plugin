<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlockTranslation as BaseSharedBlockTranslation;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_happy_cms__shared_block_translation')]
class SharedBlockTranslation extends BaseSharedBlockTranslation
{
}
