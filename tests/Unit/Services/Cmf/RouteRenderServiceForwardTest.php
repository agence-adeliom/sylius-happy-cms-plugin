<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RouteRenderService;
use Adeliom\SyliusHappyCMSPlugin\Services\Seo\BreadcrumbCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Controller\ParametersParser;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactory;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * A content document with a route controller is rendered by that controller, through a sub-request. The sub-request
 * must see the request as the visitor sent it: query string (pagination, preview flag) and routing attributes.
 */
final class RouteRenderServiceForwardTest extends TestCase
{
    private const string CONTROLLER = 'App\\Controller\\PageController::index';

    public function testTheForwardKeepsTheQueryStringAndTheRequestAttributes(): void
    {
        $route = $this->createStub(RouteInterface::class);
        $document = $this->document();

        $request = Request::create('/blog/?page=2');
        $request->attributes->add([
            '_controller' => 'sylius.happy_cms.plugin.controller.render::renderAction',
            '_route' => 'route_online_fr_FR_page_12',
            '_route_params' => ['_locale' => 'fr_FR'],
            'routeDocument' => $route,
        ]);

        $subRequest = $this->forwardedRequest($document, $request);

        self::assertSame('2', $subRequest->query->get('page'));
        self::assertSame('route_online_fr_FR_page_12', $subRequest->attributes->get('_route'));
        self::assertSame(['_locale' => 'fr_FR'], $subRequest->attributes->get('_route_params'));
        self::assertSame($route, $subRequest->attributes->get('routeDocument'));
        self::assertSame($document, $subRequest->attributes->get('contentDocument'));
        self::assertSame(self::CONTROLLER, $subRequest->attributes->get('_controller'));
    }

    private function document(): CmsRoutableInterface
    {
        $document = $this->createStub(CmsRoutableInterface::class);
        $document->method('isOnline')->willReturn(true);
        $document->method('isHttpCacheEnabled')->willReturn(false);
        $document->method('renderResponse')->willReturn(new Response());
        $document->method('getRouteController')->willReturn(self::CONTROLLER);

        return $document;
    }

    private function forwardedRequest(CmsRoutableInterface $document, Request $request): Request
    {
        $kernel = new class() implements HttpKernelInterface {
            public ?Request $subRequest = null;

            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                $this->subRequest = $request;

                return new Response();
            }
        };

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = new Container();
        $container->set('request_stack', $requestStack);
        $container->set('http_kernel', $kernel);

        $service = new RouteRenderService(
            $this->createStub(RouterInterface::class),
            new RequestConfigurationFactory(new ParametersParser($container, new ExpressionLanguage()), RequestConfiguration::class),
            $this->createStub(Environment::class),
            $this->createStub(EventDispatcherInterface::class),
            new BreadcrumbCollection(),
            $this->createStub(KernelInterface::class),
            $this->createStub(EntityManagerInterface::class),
            new ParameterBag(['sylius.resources' => ['app.page' => ['driver' => 'doctrine/orm', 'classes' => ['model' => $document::class]]]]),
        );
        $service->setContainer($container);

        $service->renderAction($document, $request);

        self::assertInstanceOf(Request::class, $kernel->subRequest);

        return $kernel->subRequest;
    }
}
