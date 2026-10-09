<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Command;

use Adeliom\SyliusHappyCMSPlugin\Command\IndexRoutesCommand;
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

    /**
     * @param array<int, mixed> $indexerArguments
     */
    private function command(array $indexerArguments = []): CommandTester
    {
        return new CommandTester(new IndexRoutesCommand($this->entityManager, $this->registerIndexer($indexerArguments)));
    }
}
