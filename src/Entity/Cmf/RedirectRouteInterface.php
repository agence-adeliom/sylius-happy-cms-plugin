<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Cmf;

use Sylius\Resource\Model\ResourceInterface;

interface RedirectRouteInterface extends ResourceInterface, \Symfony\Cmf\Component\Routing\RedirectRouteInterface
{
    public function setStaticPrefix(string $prefix): static;

    public function setHost(?string $pattern): static;
}
