<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Event\Route;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Resource\Metadata\Metadata;
use Symfony\Contracts\EventDispatcher\Event;

class RouteRenderServiceEvent extends Event
{
    /** @param array{
     *     metadata: Metadata,
     *     configuration: RequestConfiguration,
     *     resource: CmsRoutableInterface,
     *     route: RouteInterface,
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

    /** @param array{
     *     metadata?: Metadata,
     *     configuration?: RequestConfiguration,
     *     resource?: CmsRoutableInterface,
     *     route?: RouteInterface,
     *     preview?: bool
     * } $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }
}
