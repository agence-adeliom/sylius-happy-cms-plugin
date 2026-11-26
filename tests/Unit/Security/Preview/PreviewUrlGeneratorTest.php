<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Security\Preview;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\Route;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\PreviewUrlGenerator;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\PreviewUrlSignerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\RouterInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\Page;

final class PreviewUrlGeneratorTest extends TestCase
{
    public function testItGeneratesFromTheEntityOnlineRouteThenSigns(): void
    {
        $route = new Route();
        $route->setDefault('_locale', 'fr_FR');

        $page = new Page();
        $page->addRoute($route);

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('generate')
            ->with(RouteObjectInterface::OBJECT_BASED_ROUTE_NAME, [RouteObjectInterface::ROUTE_OBJECT => $route])
            ->willReturn('//front.example.test/ma-page');

        $signer = $this->createMock(PreviewUrlSignerInterface::class);
        $signer->expects(self::once())
            ->method('sign')
            ->with('//front.example.test/ma-page', $page, 'sylius_happy_cms.page', 'fr_FR')
            ->willReturn('https://front.example.test/ma-page?_hash=x');

        $url = (new PreviewUrlGenerator($router, $signer))->generate($page, 'sylius_happy_cms.page', 'fr_FR');

        self::assertSame('https://front.example.test/ma-page?_hash=x', $url);
    }

    public function testItDoesNotDependOnTheChannelContext(): void
    {
        $parameters = (new \ReflectionMethod(PreviewUrlGenerator::class, '__construct'))->getParameters();

        self::assertSame(
            [RouterInterface::class, PreviewUrlSignerInterface::class],
            array_map(static fn (\ReflectionParameter $parameter): string => (string) $parameter->getType(), $parameters),
        );
    }

    public function testItReturnsAnEmptyStringWithoutOnlineRoute(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');

        $signer = $this->createMock(PreviewUrlSignerInterface::class);
        $signer->expects(self::never())->method('sign');

        self::assertSame('', (new PreviewUrlGenerator($router, $signer))->generate(new Page(), 'sylius_happy_cms.page', 'fr_FR'));
    }
}
