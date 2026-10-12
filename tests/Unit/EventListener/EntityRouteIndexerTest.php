<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Event\Route\CalculateRouteStaticPrefixEvent;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing\CategoryPathResolver;
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

    public function testTheRoutesCanEndWithASlash(): void
    {
        $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}', true]);

        $this->savePage('about');
        $this->savePage('home', homepage: true);

        self::assertSame(['/about/', '/'], array_values($this->storedPrefixes()));
    }

    public function testTheFirstSupportingParentPathResolverPlacesTheRouteUnderAParentOfAnotherType(): void
    {
        $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}', false, [
            new CategoryPathResolver('/never'),
            new CategoryPathResolver(),
            new CategoryPathResolver('/too-late'),
        ]]);

        $this->savePage('about', category: 'company');
        $this->savePage('contact');

        self::assertSame(['/company/about', '/contact'], array_values($this->storedPrefixes()));
    }

    public function testListenersReceiveTheResolvedParentPath(): void
    {
        $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}', false, [new CategoryPathResolver()]]);
        $this->dispatcher->addListener(CalculateRouteStaticPrefixEvent::class, static function (CalculateRouteStaticPrefixEvent $event): void {
            $event->setRouteStaticPrefix('/shop' . $event->getRouteStaticPrefix());
        });

        $this->savePage('about', category: 'company');

        self::assertSame(['/shop/company/about'], array_values($this->storedPrefixes()));
    }
}
