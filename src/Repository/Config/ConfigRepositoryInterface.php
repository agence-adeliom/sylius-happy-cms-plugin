<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Config;

use Adeliom\SyliusEasyCrudPlugin\Repository\TranslationRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Config\ConfigInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @extends RepositoryInterface<ConfigInterface>
 */
interface ConfigRepositoryInterface extends RepositoryInterface, TranslationRepositoryInterface
{
    public function getByKey(string $key): ?ConfigInterface;
}
