<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Security\Preview;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\RouterInterface;

final class PreviewUrlGenerator implements PreviewUrlGeneratorInterface
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly PreviewUrlSignerInterface $signer,
    ) {
    }

    public function generate(CmsRoutableInterface $subject, string $resource, string $locale): string
    {
        $route = $subject->getOnlineRoute($locale);
        if (null === $route) {
            return '';
        }

        // The stored route carries the host of the entity channel: the URL targets the front of that channel.
        $url = $this->router->generate(RouteObjectInterface::OBJECT_BASED_ROUTE_NAME, [
            RouteObjectInterface::ROUTE_OBJECT => $route,
        ]);

        return $this->signer->sign($url, $subject, $resource, $locale);
    }
}
