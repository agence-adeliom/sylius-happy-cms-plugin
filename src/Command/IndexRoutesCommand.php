<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Command;

use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates or updates the CMS routes of the routable entities already in the database: entities created before the
 * plugin (data migration), or after a change of the routing options (URL pattern, trailing slash).
 *
 * Only the route tables are written (routes, and the join table between the entity and its new routes): the
 * entities are not saved, so their update listeners (timestamps such as updated_at and the sitemap lastmod, search
 * index, cache invalidation, audit…) do not run for every entity of the site. --save-entities saves them as well,
 * for projects whose listeners derive data from the routes (e.g. a search index storing the URL).
 */
#[AsCommand(
    name: 'happycms:routes:index',
    description: 'Create or update the CMS routes of the existing routable entities',
)]
final class IndexRoutesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityRouteIndexer $indexer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('class', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Routable entity class to index (repeatable); default: every mapped routable entity')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Entities flushed at once', '100')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Compute the routes without saving them (use -v to list them)')
            ->addOption('save-entities', null, InputOption::VALUE_NONE, 'Also save the entities (runs their update listeners: updated_at, search index…); default: only the routes are written')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $saveEntities = (bool) $input->getOption('save-entities');
        $batchSize = $input->getOption('batch-size');
        if (!is_numeric($batchSize) || (int) $batchSize < 1) {
            $io->error('--batch-size must be a positive integer.');

            return Command::INVALID;
        }
        $batchSize = (int) $batchSize;

        /** @var string[] $requestedClasses */
        $requestedClasses = $input->getOption('class');
        $classes = [] !== $requestedClasses ? $requestedClasses : $this->findRoutableClasses();
        foreach ($classes as $class) {
            if (!is_a($class, CmsRoutableInterface::class, true)) {
                $io->error(sprintf('"%s" does not implement %s.', $class, CmsRoutableInterface::class));

                return Command::INVALID;
            }
        }

        $total = 0;
        /** @var list<CmsRoutableInterface> $batch */
        $batch = [];
        foreach ($classes as $class) {
            $count = 0;
            $query = $this->entityManager->createQueryBuilder()->select('entity')->from($class, 'entity')->getQuery();
            foreach ($query->toIterable() as $entity) {
                assert($entity instanceof CmsRoutableInterface);
                $this->indexer->index($entity);
                $batch[] = $entity;
                foreach ($entity->getRoutes() as $route) {
                    $io->writeln(sprintf('  %s <info>%s</info>', $route->getName(), $route->getStaticPrefix()), OutputInterface::VERBOSITY_VERBOSE);
                }

                if (0 === ++$count % $batchSize) {
                    $this->endBatch($batch, $dryRun, $saveEntities);
                    $batch = [];
                }
            }
            $this->endBatch($batch, $dryRun, $saveEntities);
            $batch = [];

            $io->writeln(sprintf('%s: %d entities', $class, $count));
            $total += $count;
        }

        $io->success(sprintf($dryRun ? '%d entities indexed (dry run, nothing saved).' : '%d entities indexed.', $total));

        return Command::SUCCESS;
    }

    /**
     * @return class-string<CmsRoutableInterface>[] the root classes of the mapped routable entities
     */
    private function findRoutableClasses(): array
    {
        $classes = [];
        /** @var ClassMetadata<object> $metadata */
        foreach ($this->entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            $class = $metadata->rootEntityName;
            if (!$metadata->isMappedSuperclass && !$metadata->isEmbeddedClass && is_a($class, CmsRoutableInterface::class, true)) {
                $classes[$class] = $class;
            }
        }

        return array_values($classes);
    }

    /**
     * @param list<CmsRoutableInterface> $entities
     */
    private function endBatch(array $entities, bool $dryRun, bool $saveEntities): void
    {
        if ($dryRun) {
            $this->entityManager->clear();

            return;
        }

        if ($saveEntities) {
            $this->entityManager->flush();
            $this->entityManager->clear();

            return;
        }

        // A new route makes the route collection of its entity dirty, and Doctrine then schedules an update of the
        // entity (with its listeners). The collections are marked clean before the flush, which writes the routes
        // only, and their new join rows are written afterwards by the collection persister (route ids now known).
        /** @var list<PersistentCollection<array-key, object>> $collections */
        $collections = [];
        foreach ($entities as $entity) {
            $routes = $entity->getRoutes();
            if ($routes instanceof PersistentCollection && $routes->isDirty()) {
                $routes->setDirty(false);
                $collections[] = $routes;
            }
        }

        $this->entityManager->wrapInTransaction(function () use ($collections): void {
            $this->entityManager->flush();
            $unitOfWork = $this->entityManager->getUnitOfWork();
            foreach ($collections as $routes) {
                $unitOfWork->getCollectionPersister($routes->getMapping())->update($routes);
                $routes->takeSnapshot();
            }
        });
        $this->entityManager->clear();
    }
}
