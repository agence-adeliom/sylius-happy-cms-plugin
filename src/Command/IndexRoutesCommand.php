<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Command;

use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates or updates the CMS routes of the routable entities already in the database: entities created before the
 * plugin (data migration), or after a change of the routing options (URL pattern, trailing slash).
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
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
        foreach ($classes as $class) {
            $count = 0;
            $query = $this->entityManager->createQueryBuilder()->select('entity')->from($class, 'entity')->getQuery();
            foreach ($query->toIterable() as $entity) {
                assert($entity instanceof CmsRoutableInterface);
                $this->indexer->index($entity);
                foreach ($entity->getRoutes() as $route) {
                    $io->writeln(sprintf('  %s <info>%s</info>', $route->getName(), $route->getStaticPrefix()), OutputInterface::VERBOSITY_VERBOSE);
                }

                if (0 === ++$count % $batchSize) {
                    $this->endBatch($dryRun);
                }
            }
            $this->endBatch($dryRun);

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

    private function endBatch(bool $dryRun): void
    {
        if (!$dryRun) {
            $this->entityManager->flush();
        }
        $this->entityManager->clear();
    }
}
