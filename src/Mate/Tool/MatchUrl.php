<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Mate\Tool;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Mate\CmsReader;
use Sylius\MateExtension\Kernel\HostContainerProvider;
use Sylius\MateExtension\Output\Envelope;
use Symfony\AI\Mate\Attribute\MateTool;
use Symfony\Cmf\Component\Routing\ChainRouterInterface;
use Symfony\Cmf\Component\Routing\DynamicRouter;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

final class MatchUrl
{
    /** Candidate CMF routes reported when nothing matches. */
    private const MAX_CANDIDATES = 10;

    public function __construct(
        private readonly HostContainerProvider $host,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[MateTool(
        name: 'happy_cms_match_url',
        description: 'Resolve a front URL like the router does: matched route, whether it is a CMS (CMF) route or a Symfony one, controller, parameters and, for a CMS route, the content it serves (class, id, easy-crud alias, publication state, isOnline(), template, controller, routes per locale). When nothing matches: the CMF candidate routes and whether the URL with or without trailing slash matches. Pass a full URL (scheme and host) when routes depend on the host. The kernel.request listeners (redirections) do not run.',
    )]
    public function __invoke(string $url, string $method = 'GET'): array
    {
        return Envelope::guard(fn (): array => $this->match($url, strtoupper($method)));
    }

    /**
     * @return array<string, mixed>
     */
    private function match(string $url, string $method): array
    {
        $reader = new CmsReader($this->host->getContainer());
        $router = $reader->service('router');
        if (!$router instanceof RequestMatcherInterface) {
            return Envelope::error('no_router', 'The "router" service does not match requests.');
        }
        $provider = $reader->service('cmf_routing.route_provider');
        $provider = $provider instanceof RouteProviderInterface ? $provider : null;

        $request = Request::create($url, $method);

        try {
            $parameters = $router->matchRequest($request);
        } catch (RoutingException $exception) {
            return Envelope::items([[
                'url' => $url,
                'matched' => false,
                'error' => sprintf('%s: %s', (new \ReflectionClass($exception))->getShortName(), strtok($exception->getMessage(), "\n")),
                'trailing_slash_variant' => $this->trailingSlashVariant($router, $url, $method),
                'cms_candidates' => null === $provider ? null : $this->candidates($provider, $request),
            ]]);
        }

        $routeName = \is_string($parameters[RouteObjectInterface::ROUTE_NAME] ?? null) ? $parameters[RouteObjectInterface::ROUTE_NAME] : null;
        $cmsRoute = null !== $routeName && $this->isSymfonyRoute($router, $routeName) ? null : $this->cmsRoute($provider, $parameters, $routeName);
        $locale = \is_string($parameters['_locale'] ?? null) ? $parameters['_locale'] : $reader->defaultLocale();

        $item = [
            'url' => $url,
            'matched' => true,
            'route' => $routeName,
            'router' => null !== $cmsRoute ? 'cmf' : 'symfony',
            'controller' => $this->stringify($parameters[RouteObjectInterface::CONTROLLER_NAME] ?? null),
            'parameters' => $this->scalarParameters($parameters),
        ];

        if ($cmsRoute instanceof Route) {
            $item['cms_route'] = array_filter([
                'static_prefix' => $cmsRoute instanceof RouteInterface ? $cmsRoute->getStaticPrefix() : null,
                'path' => $cmsRoute->getPath(),
                'host' => $cmsRoute->getHost() ?: null,
                'condition' => $cmsRoute->getCondition() ?: null,
            ], static fn (mixed $value): bool => null !== $value);
        }

        $contentId = $parameters[RouteObjectInterface::CONTENT_ID] ?? ($cmsRoute?->getDefault(RouteObjectInterface::CONTENT_ID));
        if (\is_string($contentId)) {
            $item['content'] = $this->content($reader, $contentId, $locale);
        }

        return Envelope::items([$item]);
    }

    /**
     * Whether a Symfony router of the chain declares the route: its name is then not looked up in the CMS routes.
     */
    private function isSymfonyRoute(RequestMatcherInterface $router, string $routeName): bool
    {
        $routers = $router instanceof ChainRouterInterface ? $router->all() : [$router];
        foreach ($routers as $candidate) {
            if (!$candidate instanceof DynamicRouter && $candidate instanceof RouterInterface &&
                null !== $candidate->getRouteCollection()->get($routeName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The CMF route object behind the match: given by the dynamic router, or loaded by its name.
     *
     * @param array<mixed> $parameters
     */
    private function cmsRoute(?RouteProviderInterface $provider, array $parameters, ?string $routeName): ?Route
    {
        $route = $parameters[RouteObjectInterface::ROUTE_OBJECT] ?? null;
        if ($route instanceof Route) {
            return $route;
        }

        if (null === $provider || null === $routeName) {
            return null;
        }

        try {
            return $provider->getRouteByName($routeName);
        } catch (RoutingException) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function content(CmsReader $reader, string $contentId, string $locale): array
    {
        [$class, $id] = explode(':', $contentId, 2) + [1 => ''];
        if (!class_exists($class) || '' === $id) {
            return ['content_id' => $contentId, 'error' => 'The content id is not "<class>:<id>".'];
        }

        $content = $reader->find($class, $id);
        if (null === $content) {
            return ['content_id' => $contentId, 'error' => 'No entity has this id: the route outlives its content.'];
        }

        $resource = $reader->resource($class);
        $description = [
            'class' => $content::class,
            'id' => $id,
            'alias' => $resource['alias'] ?? null,
        ];

        if (!$content instanceof CmsRoutableInterface) {
            return $description;
        }

        $content->setCurrentLocale($locale);

        return array_filter($description + [
            'online' => $content->isOnline(),
            'state' => match (true) {
                $content->isStatePublished() => 'published',
                $content->isStatePending() => 'pending',
                $content->isStateUnpublished() => 'unpublished',
                default => null,
            },
            'date_published' => $content->isDatePublished(),
            'note' => $content->isOnline() ? null : 'Not online: RouteRenderService answers 404 ("Document is not published") outside the preview; a controller of the application may differ.',
            'template' => $content->getRouteTemplate(),
            'controller' => $content->getRouteController(),
            'routes' => array_values(array_map(static fn (RouteInterface $route): array => [
                'name' => $route->getName(),
                'static_prefix' => $route->getStaticPrefix(),
            ], $content->getRoutes()->toArray())),
        ], static fn (mixed $value): bool => null !== $value);
    }

    /**
     * Whether the URL with its trailing slash toggled matches: a 404 caused by the trailing slash, or the target of a
     * trailing slash redirection.
     *
     * @return array{url: string, route: string|null}|null
     */
    private function trailingSlashVariant(RequestMatcherInterface $router, string $url, string $method): ?array
    {
        $parts = parse_url($url);
        $path = \is_array($parts) ? ($parts['path'] ?? '/') : '/';
        if ('/' === $path) {
            return null;
        }

        $variantPath = str_ends_with($path, '/') ? rtrim($path, '/') : $path . '/';
        $variant = (string) preg_replace('#^((?:[a-z][a-z0-9+.-]*:)?//[^/?\#]*)?' . preg_quote($path, '#') . '#i', '${1}' . $variantPath, $url, 1);

        try {
            $parameters = $router->matchRequest(Request::create($variant, $method));
        } catch (RoutingException) {
            return null;
        }

        return ['url' => $variant, 'route' => $this->stringify($parameters[RouteObjectInterface::ROUTE_NAME] ?? null)];
    }

    /**
     * @return list<array{name: string, path: string}>
     */
    private function candidates(RouteProviderInterface $provider, Request $request): array
    {
        $candidates = [];
        foreach ($provider->getRouteCollectionForRequest($request) as $name => $route) {
            $candidates[] = ['name' => $name, 'path' => $route->getPath()];
            if (\count($candidates) >= self::MAX_CANDIDATES) {
                break;
            }
        }

        return $candidates;
    }

    /**
     * @param array<mixed> $parameters
     *
     * @return array<string, scalar|null>
     */
    private function scalarParameters(array $parameters): array
    {
        $scalars = [];
        foreach ($parameters as $name => $value) {
            if (!\is_string($name) || \in_array($name, [RouteObjectInterface::ROUTE_NAME, RouteObjectInterface::CONTROLLER_NAME, RouteObjectInterface::ROUTE_OBJECT], true)) {
                continue;
            }
            if (null === $value || \is_scalar($value)) {
                $scalars[$name] = $value;
            } elseif (\is_object($value)) {
                $scalars[$name] = get_debug_type($value);
            }
        }

        return $scalars;
    }

    private function stringify(mixed $value): ?string
    {
        return match (true) {
            null === $value => null,
            \is_string($value) => $value,
            \is_array($value) => implode('::', array_map(static fn (mixed $part): string => \is_object($part) ? $part::class : (\is_scalar($part) ? (string) $part : get_debug_type($part)), $value)),
            default => get_debug_type($value),
        };
    }
}
