<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Command;

use Adeliom\SyliusHappyCMSPlugin\Command\IndexRoutesCommand;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing\IndexedPage;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing\IndexedPageTranslation;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing\RouteIndexerTestCase;

final class IndexRoutesCommandTest extends RouteIndexerTestCase
{
    #[DataProvider('indexOnFlushModes')]
    public function testTheRoutesOfExistingEntitiesAreCreated(bool $indexOnFlush): void
    {
        $about = $this->savePage('about');
        $contact = $this->savePage('contact');
        $team = $this->savePage('team');
        self::assertSame([], $this->storedPrefixes(), 'Entities saved before the indexer was registered have no route.');

        $tester = $this->command(['indexOnFlush' => $indexOnFlush]);
        $tester->execute(['--batch-size' => '2']);

        $tester->assertCommandIsSuccessful();
        $routeName = static fn (?int $id): string => 'route_online_en_US_' . str_replace('\\', '_', IndexedPage::class) . '_' . $id;
        self::assertSame([
            $routeName($about->getId()) => '/en_US/about',
            $routeName($contact->getId()) => '/en_US/contact',
            $routeName($team->getId()) => '/en_US/team',
        ], $this->storedPrefixes());
        self::assertStringContainsString('3 entities indexed.', $tester->getDisplay());
    }

    #[DataProvider('indexOnFlushModes')]
    public function testRunningItAgainUpdatesTheExistingRoutes(bool $indexOnFlush): void
    {
        $this->savePage('about');
        $this->command(['indexOnFlush' => $indexOnFlush])->execute([]);

        $this->command(['urlPattern' => '{{other_entity_path}}{{current_entity_path}}', 'trailingSlash' => true, 'indexOnFlush' => $indexOnFlush])->execute([]);

        self::assertSame(['/about/'], array_values($this->storedPrefixes()));
    }

    #[DataProvider('indexOnFlushModes')]
    public function testOnlyTheRoutesAreWrittenTheEntitiesAreNotSaved(bool $indexOnFlush): void
    {
        $about = $this->savePage('about');
        $contact = $this->savePage('contact');
        $updated = $this->spyUpdates();

        $tester = $this->command(['indexOnFlush' => $indexOnFlush]);
        $tester->execute(['--batch-size' => '1']);
        // second run: existing routes updated, links unchanged
        $this->command(['urlPattern' => '{{other_entity_path}}{{current_entity_path}}', 'trailingSlash' => true, 'indexOnFlush' => $indexOnFlush])->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertNotContains(IndexedPage::class, $updated->classes, 'No update of the entities: their listeners (timestamps, search index, sitemap) do not run.');
        self::assertSame(['/about/', '/contact/'], array_values($this->storedPrefixes()));
        $this->entityManager->clear();
        foreach ([$about->getId() => '/about/', $contact->getId() => '/contact/'] as $id => $path) {
            $page = $this->entityManager->find(IndexedPage::class, $id);
            self::assertInstanceOf(IndexedPage::class, $page);
            self::assertCount(1, $page->getRoutes(), 'The entity is linked to its route.');
            self::assertSame($path, $page->getRoutes()->first()->getStaticPrefix());
        }
    }

    #[DataProvider('indexOnFlushModes')]
    public function testTheEntitiesCanBeSavedOnRequest(bool $indexOnFlush): void
    {
        $about = $this->savePage('about');
        $updated = $this->spyUpdates();

        $tester = $this->command(['indexOnFlush' => $indexOnFlush]);
        $tester->execute(['--save-entities' => true]);

        $tester->assertCommandIsSuccessful();
        self::assertContains(IndexedPage::class, $updated->classes);
        $this->entityManager->clear();
        $page = $this->entityManager->find(IndexedPage::class, $about->getId());
        self::assertInstanceOf(IndexedPage::class, $page);
        self::assertCount(1, $page->getRoutes());
    }

    #[DataProvider('indexOnFlushModes')]
    public function testTheEntitiesCanBeSavedInSeveralBatches(bool $indexOnFlush): void
    {
        $this->savePage('about');
        $this->savePage('contact');
        $this->savePage('team');

        $tester = $this->command(['indexOnFlush' => $indexOnFlush]);
        $tester->execute(['--save-entities' => true, '--batch-size' => '1']);

        $tester->assertCommandIsSuccessful();
        self::assertSame(['/en_US/about', '/en_US/contact', '/en_US/team'], array_values($this->storedPrefixes()));
    }

    #[DataProvider('indexOnFlushModes')]
    public function testADryRunListsTheRoutesWithoutSavingThem(bool $indexOnFlush): void
    {
        $this->savePage('about');

        $tester = $this->command(['indexOnFlush' => $indexOnFlush]);
        $tester->execute(['--dry-run' => true], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('/en_US/about', $tester->getDisplay());
        self::assertSame([], $this->storedPrefixes());
    }

    #[DataProvider('indexOnFlushModes')]
    public function testOnlyRoutableClassesAreAccepted(bool $indexOnFlush): void
    {
        $tester = $this->command(['indexOnFlush' => $indexOnFlush]);

        self::assertSame(Command::INVALID, $tester->execute(['--class' => [IndexedPageTranslation::class]]));
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function indexOnFlushModes(): iterable
    {
        yield 'legacy listener' => [false];
        yield 'index on flush' => [true];
    }

    private function spyUpdates(): object
    {
        $spy = new class() {
            /** @var list<class-string> */
            public array $classes = [];

            public function postUpdate(PostUpdateEventArgs $event): void
            {
                $this->classes[] = $event->getObject()::class;
            }
        };
        $this->entityManager->getEventManager()->addEventListener([Events::postUpdate], $spy);

        return $spy;
    }

    /**
     * @param array<int|string, mixed> $indexerArguments
     */
    private function command(array $indexerArguments = []): CommandTester
    {
        return new CommandTester(new IndexRoutesCommand($this->entityManager, $this->registerIndexer($indexerArguments)));
    }
}
