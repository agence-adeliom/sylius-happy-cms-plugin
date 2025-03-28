<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Event\Route;

use Symfony\Contracts\EventDispatcher\Event;

class RouteRenderServiceEvent extends Event
{
    /** @param array{
     *     metadata: \Sylius\Resource\Metadata\Metadata,
     *     configuration: \Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration,
     *     resource: \Sylius\Component\Resource\Model\ResourceInterface,
     *     route: \Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Route,
     *     preview: bool
     * } $parameters
     */
    public function __construct(
        private array $parameters,
    ) {
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }
}
