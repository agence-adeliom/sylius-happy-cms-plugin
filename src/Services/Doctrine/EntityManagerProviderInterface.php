<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Doctrine;

use Doctrine\ORM\EntityManagerInterface;

interface EntityManagerProviderInterface
{
    public function getEntityManager(): EntityManagerInterface;
}
