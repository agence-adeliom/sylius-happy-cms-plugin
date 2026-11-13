<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Controller\PageBuilder;

use Adeliom\SyliusHappyCMSPlugin\Controller\PageBuilder\PageBuilderController;
use Adeliom\SyliusHappyCMSPlugin\Event\PageBuilder\ContentPublishedEvent;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\Page;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\PageContentBlock;

/**
 * Publishing in the page builder dispatches ContentPublishedEvent once the blocks are saved, so that a project can
 * react (purge the HTTP cache of the page, reindex it…).
 */
final class ContentPublishedEventTest extends TestCase
{
    public function testPublishingDispatchesTheEventAfterTheBlocksAreSaved(): void
    {
        $page = new Page();
        $block = new PageContentBlock();
        $block->setLocale('fr_FR');
        $block->setDraftData(['title' => 'Hello']);
        $page->addContentBlock($block);

        $flushed = false;
        $entityManager = $this->entityManager($page, $flushed);

        /** @var list<array{ContentPublishedEvent, bool}> $dispatched */
        $dispatched = [];
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(ContentPublishedEvent::class, static function (ContentPublishedEvent $event) use (&$dispatched, &$flushed): void {
            $dispatched[] = [$event, $flushed];
        });

        $request = Request::create('/happy-cms-builder/app.page/12', 'GET', ['publish' => '1', 'locales' => ['fr_FR']]);
        $this->controller($entityManager, $dispatcher, $request)->builderAction($request, 'app.page', 12);

        self::assertCount(1, $dispatched);
        [$event, $flushedBefore] = $dispatched[0];
        self::assertSame($page, $event->getEntity());
        self::assertSame(['fr_FR'], $event->getLocales());
        self::assertTrue($flushedBefore);
    }

    private function entityManager(Page $page, bool &$flushed): EntityManagerInterface
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn($page);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);
        $entityManager->method('flush')->willReturnCallback(static function () use (&$flushed): void {
            $flushed = true;
        });

        return $entityManager;
    }

    private function controller(EntityManagerInterface $entityManager, EventDispatcher $dispatcher, Request $request): PageBuilderController
    {
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/happy-cms-builder/app.page/12');

        $container = new Container();
        $container->set('request_stack', $requestStack);
        $container->set('router', $router);

        $controller = new PageBuilderController(
            $entityManager,
            new ParameterBag(['sylius.resources' => ['app.page' => ['classes' => ['model' => Page::class]]]]),
            $this->createStub(LocaleProviderInterface::class),
            new BlockCollection([]),
            $this->createStub(TranslatorInterface::class),
            new SharedBlockCollection([]),
            $dispatcher,
        );
        $controller->setContainer($container);

        return $controller;
    }
}
