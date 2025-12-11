<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Command;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'happycms:migrate:content-to-blocks',
    description: 'Migrate content from JSON field to ContentBlock entities',
)]
class MigrateContentToBlocksCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Execute the migration in dry-run mode (no changes will be persisted)',
            )
            ->addOption(
                'resource',
                'r',
                InputOption::VALUE_OPTIONAL,
                'Migrate only a specific resource (e.g., "sylius_happy_cms.page")',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $specificResource = $input->getOption('resource');

        if ($dryRun) {
            $io->warning('Running in DRY-RUN mode. No changes will be persisted.');
        }

        $io->title('Content to ContentBlocks Migration');

        // Get Sylius resources configuration
        $resources = $this->parameterBag->get('sylius.resources');

        if (!is_array($resources)) {
            $io->error('No Sylius resources found in configuration.');

            return Command::FAILURE;
        }

        $io->section('Scanning for CmsRoutable entities...');

        $eligibleEntities = $this->findEligibleEntities($resources, $io);

        if (empty($eligibleEntities)) {
            $io->warning('No eligible entities found.');

            return Command::SUCCESS;
        }

        $io->table(
            ['Resource', 'Entity Class'],
            array_map(fn ($key, $entity) => [$key, $entity], array_keys($eligibleEntities), $eligibleEntities),
        );

        // Filter by specific resource if requested
        if ($specificResource && is_string($specificResource)) {
            if (!isset($eligibleEntities[$specificResource])) {
                $io->error(sprintf('Resource "%s" not found in eligible entities.', $specificResource));

                return Command::FAILURE;
            }
            $eligibleEntities = [$specificResource => $eligibleEntities[$specificResource]];
            $io->note(sprintf('Processing only resource: %s', $specificResource));
        }

        $io->section('Starting migration...');

        $totalMigrated = 0;
        $totalErrors = 0;

        foreach ($eligibleEntities as $resourceName => $entityClass) {
            $io->writeln(sprintf('Processing resource: <info>%s</info> (<comment>%s</comment>)', $resourceName, $entityClass));

            try {
                $result = $this->migrateResource($entityClass, $dryRun, $io);
                $totalMigrated += $result['migrated'];
                $totalErrors += $result['errors'];
            } catch (\Exception $e) {
                $io->error(sprintf('Error processing resource %s: %s', $resourceName, $e->getMessage()));
                ++$totalErrors;
            }
        }

        $io->newLine();
        $io->success(sprintf(
            'Migration completed! %d content blocks created, %d errors.',
            $totalMigrated,
            $totalErrors,
        ));

        if ($dryRun) {
            $io->note('This was a DRY-RUN. Run without --dry-run to persist changes.');
        }

        return $totalErrors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Find all entities that implement CmsRoutableInterface, ResourceInterface and TranslatableInterface.
     *
     * @param array<string, mixed> $resources
     *
     * @return array<string, class-string>
     */
    private function findEligibleEntities(array $resources, SymfonyStyle $io): array
    {
        $eligibleEntities = [];

        foreach ($resources as $resourceName => $resourceConfig) {
            if (!is_array($resourceConfig) || !isset($resourceConfig['classes']['model'])) {
                continue;
            }

            $entityClass = $resourceConfig['classes']['model'];

            if (!class_exists($entityClass)) {
                $io->warning(sprintf('Entity class "%s" not found, skipping...', $entityClass));

                continue;
            }

            $reflection = new \ReflectionClass($entityClass);

            // Check if entity implements the required interfaces
            if (
                $reflection->implementsInterface(CmsRoutableInterface::class) &&
                $reflection->implementsInterface(ResourceInterface::class) &&
                $reflection->implementsInterface(TranslatableInterface::class)
            ) {
                $eligibleEntities[$resourceName] = $entityClass;
            }
        }

        return $eligibleEntities;
    }

    /**
     * Migrate a specific resource.
     *
     * @param class-string $entityClass
     *
     * @return array{migrated: int, errors: int}
     */
    private function migrateResource(string $entityClass, bool $dryRun, SymfonyStyle $io): array
    {
        $repository = $this->entityManager->getRepository($entityClass);
        $entities = $repository->findAll();

        $migrated = 0;
        $errors = 0;

        if (empty($entities)) {
            $io->writeln('  No entities found.');

            return ['migrated' => 0, 'errors' => 0];
        }

        $io->progressStart(count($entities));

        foreach ($entities as $entity) {
            try {
                if ($entity instanceof TranslatableInterface) {
                    $result = $this->migrateEntity($entity, $entityClass, $dryRun, $io);
                    $migrated += $result;
                }
            } catch (\Exception $e) {
                $io->error(sprintf('  Error migrating entity ID %s: %s', method_exists($entity, 'getId') ? $entity->getId() : 'unknown', $e->getMessage()));
                ++$errors;
            }
            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->writeln(sprintf('  Migrated: <info>%d</info> content blocks', $migrated));

        return ['migrated' => $migrated, 'errors' => $errors];
    }

    /**
     * Migrate a single entity by processing all its translations.
     *
     * @param class-string $entityClass
     */
    private function migrateEntity(TranslatableInterface $entity, string $entityClass, bool $dryRun, SymfonyStyle $io): int
    {
        $migrated = 0;

        // Get translations
        $translations = $entity->getTranslations();

        foreach ($translations as $translation) {
            if (!$translation instanceof TranslationInterface) {
                continue;
            }

            // Check if translation has a getContent method
            if (!method_exists($translation, 'getContent')) {
                continue;
            }

            $content = $translation->getContent();

            if (empty($content) || !is_array($content)) {
                continue;
            }

            $locale = $translation->getLocale();

            $naturalPosition = 0;
            // Parse content and create ContentBlocks
            foreach ($content as $blockKey => $blockData) {
                if (!is_array($blockData) || !isset($blockData['block_type'])) {
                    $io->warning(sprintf('  Invalid block data for key "%s", skipping...', $blockKey));

                    continue;
                }

                try {
                    $this->createContentBlock($entity, $entityClass, $blockData, $locale, $dryRun, $naturalPosition);
                    ++$naturalPosition;
                    ++$migrated;
                } catch (\Exception $e) {
                    $io->error(sprintf('  Error creating content block: %s', $e->getMessage()));
                }
            }
        }

        if (!$dryRun && $migrated > 0) {
            $this->entityManager->flush();
        }

        return $migrated;
    }

    /**
     * Create a ContentBlock from block data.
     *
     * @param class-string $entityClass
     * @param array{
     *     block_type: string,
     *     position?: int,
     *     block_published?: string,
     * } $blockData
     */
    private function createContentBlock(TranslatableInterface $entity, string $entityClass, array $blockData, ?string $locale, bool $dryRun, int $naturalPosition): void
    {
        $blockType = $blockData['block_type'];
        $position = $naturalPosition;
        $published = isset($blockData['block_published']) && '1' === $blockData['block_published'];

        // Remove metadata fields from block data
        $contentData = $blockData;
        unset($contentData['block_type'], $contentData['position'], $contentData['block_published']);

        // Determine the ContentBlock class based on the entity
        $contentBlockClass = $this->getContentBlockClass($entityClass);

        if (!$contentBlockClass) {
            throw new \RuntimeException(sprintf('Could not determine ContentBlock class for entity "%s"', $entityClass));
        }

        if (!is_string($blockType)) {
            throw new \RuntimeException(sprintf('Could not determine block type class for entity "%s"', $entityClass));
        }

        // Create ContentBlock instance
        $contentBlock = new $contentBlockClass();

        if (!$contentBlock instanceof ContentBlockInterface) {
            throw new \RuntimeException(sprintf('Class "%s" does not implement ContentBlockInterface', $contentBlockClass));
        }

        $contentBlock->setType($blockType);
        $contentBlock->setLocale($locale ?? 'en');
        $contentBlock->setPosition($position);
        $contentBlock->setPublished($published);
        $contentBlock->setLayer(null);

        // Set data
        $contentBlock->setDraftData($contentData);
        if ($published) {
            $contentBlock->setPublishedData($contentData);
        }

        // Link to entity
        if (method_exists($contentBlock, 'setContentOwner') && $entity instanceof ResourceInterface) {
            $contentBlock->setContentOwner($entity);
        }

        if (method_exists($entity, 'addContentBlock')) {
            $entity->addContentBlock($contentBlock);
        }

        if (!$dryRun) {
            $this->entityManager->persist($contentBlock);
        }
    }

    /**
     * Determine the ContentBlock class for a given entity class.
     *
     * @param class-string $entityClass
     *
     * @return class-string|null
     */
    private function getContentBlockClass(string $entityClass): ?string
    {
        // Extract namespace and base entity name
        $entityName = (new \ReflectionClass($entityClass))->getShortName();

        // Build ContentBlock class name: PageInterface => PageContentBlock
        $contentBlockClassName = str_replace('Interface', '', $entityName) . 'ContentBlock';

        // Get namespace from entity
        $entityNamespace = (new \ReflectionClass($entityClass))->getNamespaceName();

        // Try to find the ContentBlock class in the same namespace
        $contentBlockClass = $entityNamespace . '\\' . $contentBlockClassName;

        if (class_exists($contentBlockClass)) {
            return $contentBlockClass;
        }

        // If not found, try without namespace hierarchy (for custom implementations)
        // This allows users to define their own ContentBlock classes
        return null;
    }
}
