<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing;

use Doctrine\ORM\Mapping as ORM;

/**
 * Stands for the Sylius channel targeted by EntityRouteTrait::$channel (never set in these tests).
 */
#[ORM\Entity]
#[ORM\Table(name: 'indexed_channel')]
class IndexedChannel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;
}
