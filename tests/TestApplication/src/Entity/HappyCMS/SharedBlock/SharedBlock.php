<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlock as BaseSharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlockTranslationInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Repository\HappyCMS\SharedBlock\SharedBlockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SharedBlockRepository::class)]
#[ORM\Table(name: 'sylius_happy_cms__shared_block')]
class SharedBlock extends BaseSharedBlock
{
    protected function createTranslation(): SharedBlockTranslationInterface
    {
        return new SharedBlockTranslation();
    }

    public static function getTranslationClass(): string
    {
        return SharedBlockTranslation::class;
    }
}
