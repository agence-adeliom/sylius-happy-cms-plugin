<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Repository\HappyCMS\Cmf\RouteRepository;
use Tests\Adeliom\SyliusHappyCMSPlugin\Repository\HappyCMS\Page\PageRepository;
use Tests\Adeliom\SyliusHappyCMSPlugin\Service\RepositoryInterfacesConsumer;

final class RepositoryInterfacesAutowiringTest extends KernelTestCase
{
    public function testThePageAndRouteRepositoryInterfacesAreAutowiredToTheResourceRepositories(): void
    {
        self::bootKernel();

        $consumer = self::getContainer()->get(RepositoryInterfacesConsumer::class);
        self::assertInstanceOf(RepositoryInterfacesConsumer::class, $consumer);

        self::assertInstanceOf(PageRepository::class, $consumer->pages);
        self::assertSame(self::getContainer()->get('sylius_happy_cms.repository.page'), $consumer->pages);
        self::assertInstanceOf(RouteRepository::class, $consumer->routes);
        self::assertSame(self::getContainer()->get('sylius_happy_cms.repository.route'), $consumer->routes);
    }
}
