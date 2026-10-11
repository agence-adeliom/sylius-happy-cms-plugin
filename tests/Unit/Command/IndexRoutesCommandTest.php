<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Command;

use Adeliom\SyliusHappyCMSPlugin\Command\IndexRoutesCommand;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing\IndexedPage;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing\IndexedPageTranslation;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing\RouteIndexerTestCase;

final class IndexRoutesCommandTest extends RouteIndexerTestCase
{
    public function testTheRoutesOfExistingEntitiesAreCreated(): void
    {
        $about = $this->savePage('about');
        $contact = $this->savePage('contact');
        $team = $this->savePage('team');
        self::assertSame([], $this->storedPrefixes(), 'Entities saved before the indexer was registered have no route.');

        $tester = $this->command();
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

    public function testRunningItAgainUpdatesTheExistingRoutes(): void
    {
        $this->savePage('about');
        $this->command()->execute([]);

        $this->command(['{{other_entity_path}}{{current_entity_path}}', true])->execute([]);

        self::assertSame(['/about/'], array_values($this->storedPrefixes()));
    }

    public function testOnlyTheRoutesAreWrittenTheEntitiesAreNotSaved(): void
    {
        $about = $this->savePage('about');
        $contact = $this->savePage('contact');
        $updated = $this->spyUpdates();

        $tester = $this->command();
        $tester->execute(['--batch-size' => '1']);
        // second run: existing routes updated, links unchanged
        $this->command(['{{other_entity_path}}{{current_entity_path}}', true])->execute([]);

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

    public function testTheEntitiesCanBeSavedOnRequest(): void
    {
        $about = $this->savePage('about');
        $updated = $this->spyUpdates();

        $tester = $this->command();
        $tester->execute(['--save-entities' => true]);

        $tester->assertCommandIsSuccessful();
        self::assertContains(IndexedPage::class, $updated->classes);
        $this->entityManager->clear();
        $page = $this->entityManager->find(IndexedPage::class, $about->getId());
        self::assertInstanceOf(IndexedPage::class, $page);
        self::assertCount(1, $page->getRoutes());
    }

    public function testADryRunListsTheRoutesWithoutSavingThem(): void
    {
        $this->savePage('about');

        $tester = $this->command();
        $tester->execute(['--dry-run' => true], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('/en_US/about', $tester->getDisplay());
        self::assertSame([], $this->storedPrefixes());
    }

    public function testOnlyRoutableClassesAreAccepted(): void
    {
        $tester = $this->command();

        self::assertSame(Command::INVALID, $tester->execute(['--class' => [IndexedPageTranslation::class]]));
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
     * @param array<int, mixed> $indexerArguments
     */
    private function command(array $indexerArguments = []): CommandTester
    {
        return new CommandTester(new IndexRoutesCommand($this->entityManager, $this->registerIndexer($indexerArguments)));
    }
}
