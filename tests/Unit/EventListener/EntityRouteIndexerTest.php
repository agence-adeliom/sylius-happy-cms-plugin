<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Event\Route\CalculateRouteStaticPrefixEvent;
use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing\RouteIndexerTestCase;

final class EntityRouteIndexerTest extends RouteIndexerTestCase
{
    public function testARouteIsCreatedWithTheLocalePrefixByDefault(): void
    {
        $this->registerIndexer();

        $this->savePage('about');

        self::assertSame(['/en_US/about'], array_values($this->storedPrefixes()));
    }

    public function testTheUrlPatternCanLeaveTheLocaleOut(): void
    {
        $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}']);

        $this->savePage('about');
        $this->savePage('home', homepage: true);

        self::assertSame(['/about', '/'], array_values($this->storedPrefixes()));
    }

    public function testThePathOfAParentOfAnotherTypeFillsItsPlaceholder(): void
    {
        $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}']);
        $this->dispatcher->addListener(CalculateRouteStaticPrefixEvent::class, static function (CalculateRouteStaticPrefixEvent $event): void {
            $event->setRouteStaticPrefix('/' . $event->getEntity()->category);
        });

        $this->savePage('about', category: 'company');

        self::assertSame(['/company/about'], array_values($this->storedPrefixes()));
    }

    public function testRepeatedSlashesAreKeptWithoutTheCollapseOption(): void
    {
        $this->registerIndexer();
        $this->dispatcher->addListener(CalculateRouteStaticPrefixEvent::class, static function (CalculateRouteStaticPrefixEvent $event): void {
            $event->setRouteStaticPrefix('/' . $event->getEntity()->category . '/');
        });

        $this->savePage('about', category: 'company');

        self::assertSame(['/en_US/company//about'], array_values($this->storedPrefixes()));
    }

    public function testRepeatedSlashesAreCollapsedWithTheCollapseOption(): void
    {
        $this->registerIndexer([EntityRouteIndexer::DEFAULT_URL_PATTERN, true]);
        $this->dispatcher->addListener(CalculateRouteStaticPrefixEvent::class, static function (CalculateRouteStaticPrefixEvent $event): void {
            $event->setRouteStaticPrefix('/' . $event->getEntity()->category . '/');
        });

        $this->savePage('about', category: 'company');

        self::assertSame(['/en_US/company/about'], array_values($this->storedPrefixes()));
    }
}
