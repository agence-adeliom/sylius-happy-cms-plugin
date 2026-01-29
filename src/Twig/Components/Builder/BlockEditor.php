<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Components\Builder;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Service\AI\AIBundleDetector;
use Adeliom\SyliusHappyCMSPlugin\Service\AI\BlockContentGenerator;
use AllowDynamicProperties;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AllowDynamicProperties]
class BlockEditor extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true, onUpdated: 'getBlock')]
    public ?int $blockId = null;

    #[LiveProp(writable: true)]
    public string $resourceName;

    #[LiveProp(writable: true)]
    public int $entityId;

    #[LiveProp(writable: true)]
    public string $locale;

    #[LiveProp(writable: true)]
    public bool $aiTranslateEnabled = false;

    #[LiveProp(writable: true)]
    public bool $showBlockBrowser = false;

    #[LiveProp(writable: true)]
    public string $blockFilterText = '';

    #[LiveProp(writable: true)]
    public string $blockFilterCategory = 'all_blocks';

    #[LiveProp(writable: true)]
    public bool $showAIGenerator = false;

    #[LiveProp(writable: true)]
    public string $aiPrompt = '';

    #[LiveProp(writable: true)]
    public int $aiBlockCount = 3;

    #[LiveProp(writable: true)]
    public ?string $errorMessage = null;

    public ContentEditableInterface $entity;

    public function __construct(
        private readonly BlockCollection $blockCollection,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $parameterBag,
        private readonly AIBundleDetector $aiBundleDetector,
        private readonly BlockContentGenerator $blockContentGenerator,
    ) {
    }

    #[LiveAction]
    public function changeBlock(#[LiveArg('blockId')] ?int $blockId = null): void
    {
        $this->blockId = $blockId;
        $this->getBlock();
    }

    #[LiveAction]
    public function cancel(#[LiveArg('blockId')] ?int $blockId = null): void
    {
        $this->blockId = null;
        $this->getBlock();
    }

    public function getBlock(): ?ContentBlockInterface
    {
        if (null === $this->blockId) {
            return null;
        }

        // Resolve the entity class from the resource name (validates interfaces)
        $entityClass = $this->resolveEntityClass($this->resourceName);

        // Load the entity
        $this->entity = $this->loadEntity($entityClass, $this->entityId);

        $blocks = $this->entity->getContentBlocks();

        return $blocks->filter(function (ContentBlockInterface $block) {
            return $block->getId() === $this->blockId;
        })->first() ?: null;
    }

    public function hasBlock(): bool
    {
        return null !== $this->getBlock();
    }

    public function getBlockName(): ?string
    {
        $block = $this->getBlock();

        if (null === $block) {
            return null;
        }

        $blockType = $block->getType();
        if (null === $blockType) {
            return null;
        }

        $blocks = $this->blockCollection->getBlocks();
        if (!isset($blocks[$blockType])) {
            return null;
        }

        $blockConfig = $blocks[$blockType];
        $formClass = $blockConfig::class;

        // Extract the class name without namespace
        $className = (new \ReflectionClass($formClass))->getShortName();

        // Convert from CamelCase to readable format (e.g., "AccordionBlockType" -> "Accordion Block")
        return trim(preg_replace('/([A-Z])/', ' $1', str_replace('BlockType', '', $className)) ?: '');
    }

    public function getBlockPosition(): ?int
    {
        $block = $this->getBlock();

        if (null === $block) {
            return null;
        }

        return $block->getPreviewPosition();
    }

    public function getTotalBlocks(): int
    {
        if (!isset($this->entity)) {
            return 0;
        }

        return $this->entity->getContentBlocksForPreview($this->locale)->count();
    }

    #[LiveAction]
    public function togglePublished(): void
    {
        $block = $this->getBlock();
        if (null === $block) {
            return;
        }

        $block->setPreviewPublishState(
            $block->isPreviewPublished() ? ThreeStateStatusEnum::UNPUBLISHED : ThreeStateStatusEnum::PUBLISHED,
        );
        $this->entityManager->flush();

        // Dispatch event to reload iframe
        $this->dispatchBrowserEvent('block:saved', [
            'blockId' => $this->blockId,
        ]);
    }

    #[LiveAction]
    public function moveBlock(#[LiveArg('position')] int $newPosition): void
    {
        $block = $this->getBlock();
        if (null === $block) {
            return;
        }

        $totalBlocks = $this->getTotalBlocks();

        // Validate position
        if ($newPosition < 0 || $newPosition >= $totalBlocks) {
            return;
        }

        $currentPosition = $block->getPreviewPosition() ?: 0;

        // Get all blocks for this entity and locale
        $blocks = $this->entity->getContentBlocksForPreview($this->locale)
            ->filter(function (ContentBlockInterface $b) use ($block) {
                return $b->getLocale() === $block->getLocale();
            })
            ->toArray();

        // Sort by position
        usort($blocks, function (ContentBlockInterface $a, ContentBlockInterface $b) {
            return $a->getPreviewPosition() <=> $b->getPreviewPosition();
        });

        // Move the block
        if ($newPosition !== $currentPosition) {
            // Remove from current position
            array_splice($blocks, $currentPosition, 1);

            // Insert at new position
            array_splice($blocks, $newPosition, 0, [$block]);

            // Update all positions
            foreach ($blocks as $index => $b) {
                $b->setPreviewPosition($index);
            }

            $this->entityManager->flush();

            // Dispatch event to reload iframe
            $this->dispatchBrowserEvent('block:moved', [
                'blockId' => $this->blockId,
                'newPosition' => $newPosition,
            ]);

            // Dispatch event to reload iframe
            $this->dispatchBrowserEvent('block:saved', [
                'blockId' => $this->blockId,
            ]);
        }
    }

    #[LiveAction]
    public function deleteBlock(): void
    {
        $block = $this->getBlock();
        if (null === $block) {
            return;
        }

        $blockId = $this->blockId;

        // Mark the block as deleted (soft delete)
        $block->delete();
        $this->entityManager->flush();

        // Reset blockId to exit edit mode
        $this->blockId = null;

        // Dispatch event to reload iframe
        $this->dispatchBrowserEvent('block:deleted', [
            'blockId' => $blockId,
        ]);

        // Dispatch event to reload iframe
        $this->dispatchBrowserEvent('block:saved', [
            'blockId' => $this->blockId,
        ]);

        $this->dispatchBrowserEvent('block-editor:close', []);
    }

    #[LiveAction]
    public function restoreBlock(): void
    {
        $block = $this->getBlock();
        if (null === $block) {
            return;
        }

        // Restore the block
        $block->restore();
        $this->entityManager->flush();

        // Dispatch event to reload iframe
        $this->dispatchBrowserEvent('block:restored', [
            'blockId' => $this->blockId,
        ]);

        // Dispatch event to reload iframe
        $this->dispatchBrowserEvent('block:saved', [
            'blockId' => $this->blockId,
        ]);
    }

    /**
     * Check if the current locale has blocks.
     */
    public function hasBlocksForCurrentLocale(): bool
    {
        // Resolve the entity class from the resource name (validates interfaces)
        $entityClass = $this->resolveEntityClass($this->resourceName);

        // Load the entity
        $this->entity = $this->loadEntity($entityClass, $this->entityId);

        $blocksForLocale = $this->entity->getContentBlocksForPreview($this->locale);

        return $blocksForLocale->count() > 0;
    }

    /**
     * Get available locales that have blocks.
     *
     * @return array<string, string> Associative array with locale codes as keys and labels as values
     */
    public function getAvailableLocalesWithBlocks(): array
    {
        // Resolve the entity class from the resource name (validates interfaces)
        $entityClass = $this->resolveEntityClass($this->resourceName);

        // Load the entity
        $this->entity = $this->loadEntity($entityClass, $this->entityId);

        $blocks = $this->entity->getContentBlocks();

        // Get all unique locales from content blocks
        $localesWithBlocks = [];
        foreach ($blocks as $block) {
            $blockLocale = $block->getLocale();
            if ($blockLocale && $blockLocale !== $this->locale && !isset($localesWithBlocks[$blockLocale])) {
                $localesWithBlocks[$blockLocale] = $blockLocale;
            }
        }

        return $localesWithBlocks;
    }

    /**
     * Copy blocks from another locale to the current locale.
     * If AI translation is enabled, the content will be automatically translated.
     */
    #[LiveAction]
    public function copyBlocksFromLocale(#[LiveArg('sourceLocale')] string $sourceLocale): void
    {
        // Resolve the entity class from the resource name (validates interfaces)
        $entityClass = $this->resolveEntityClass($this->resourceName);

        // Load the entity
        $this->entity = $this->loadEntity($entityClass, $this->entityId);

        $blocks = $this->entity->getContentBlocks();

        // Get blocks from source locale
        $sourceBlocks = $blocks->filter(function (ContentBlockInterface $block) use ($sourceLocale) {
            return $block->getLocale() === $sourceLocale;
        })->toArray();

        // Sort by position
        usort($sourceBlocks, function (ContentBlockInterface $a, ContentBlockInterface $b) {
            return $a->getPosition() <=> $b->getPosition();
        });

        // Get the entity class name for creating new blocks
        $blockClass = get_class($sourceBlocks[0] ?? null);
        if (!$blockClass) {
            return;
        }

        // Copy each block
        foreach ($sourceBlocks as $index => $sourceBlock) {
            $newBlock = new $blockClass();

            if (!$newBlock instanceof ContentBlockInterface) {
                continue;
            }

            // Copy properties
            $newBlock->setLocale($this->locale);
            $newBlock->setType($sourceBlock->getType());
            $newBlock->setPosition($index);
            $newBlock->setPreviewPosition($index);
            $newBlock->setLayer($sourceBlock->getLayer());

            // Copy draft data (will be used as preview)
            $draftData = $sourceBlock->getDraftData();
            if (null !== $draftData) {
                // TODO: Implement AI translation when enabled
                // if ($this->aiTranslateEnabled) {
                //     $draftData = $this->translateBlockData($draftData, $sourceLocale, $this->locale);
                // }
                $newBlock->setDraftData($draftData);
            }

            // Copy published data
            $publishedData = $sourceBlock->getPublishedData();
            if (null !== $publishedData) {
                // TODO: Implement AI translation when enabled
                // if ($this->aiTranslateEnabled) {
                //     $publishedData = $this->translateBlockData($publishedData, $sourceLocale, $this->locale);
                // }
                $newBlock->setPublishedData($publishedData);
            }

            // Copy publish state
            $newBlock->setPreviewPublishState($sourceBlock->getPreviewPublishState());

            // Add to entity
            $this->entity->addContentBlock($newBlock);
            $this->entityManager->persist($newBlock);
        }

        $this->entityManager->flush();

        // Dispatch event to reload the entire page to show new blocks
        $this->dispatchBrowserEvent('blocks:copied', [
            'sourceLocale' => $sourceLocale,
            'targetLocale' => $this->locale,
            'aiTranslateEnabled' => $this->aiTranslateEnabled,
            'reload' => true,
        ]);
    }

    /**
     * Resolve the entity class from the resource name using Sylius resources configuration.
     *
     * @return class-string<ContentEditableInterface>
     */
    private function resolveEntityClass(string $resource): string
    {
        // Get Sylius resources configuration
        $resources = $this->parameterBag->get('sylius.resources');

        if (!is_array($resources)) {
            throw new \RuntimeException('No Sylius resources found in configuration.');
        }

        // Check if resource exists in configuration
        if (!isset($resources[$resource])) {
            throw new NotFoundHttpException(
                sprintf('Resource "%s" not found in Sylius configuration.', $resource),
            );
        }

        $resourceConfig = $resources[$resource];

        if (!is_array($resourceConfig) || !isset($resourceConfig['classes']['model'])) {
            throw new NotFoundHttpException(
                sprintf('Invalid resource configuration for "%s".', $resource),
            );
        }

        /** @var class-string<ContentEditableInterface> $entityClass */
        $entityClass = $resourceConfig['classes']['model'];

        if (!class_exists($entityClass)) {
            throw new NotFoundHttpException(
                sprintf('Entity class "%s" not found for resource "%s".', $entityClass, $resource),
            );
        }

        // Validate that the entity implements required interfaces
        $reflection = new \ReflectionClass($entityClass);

        if (!$reflection->implementsInterface(ContentEditableInterface::class)) {
            throw new AccessDeniedHttpException(
                sprintf('Entity "%s" does not implement ContentEditableInterface.', $entityClass),
            );
        }

        if (!$reflection->implementsInterface(CmsRoutableInterface::class)) {
            throw new AccessDeniedHttpException(
                sprintf('Entity "%s" does not implement CmsRoutableInterface.', $entityClass),
            );
        }

        if (!$reflection->implementsInterface(ResourceInterface::class)) {
            throw new AccessDeniedHttpException(
                sprintf('Entity "%s" does not implement ResourceInterface.', $entityClass),
            );
        }

        return $entityClass;
    }

    /**
     * Load an entity by its class and ID.
     *
     * @param class-string<ContentEditableInterface> $entityClass
     */
    private function loadEntity(string $entityClass, int $id): ContentEditableInterface
    {
        $repository = $this->entityManager->getRepository($entityClass);
        $entity = $repository->find($id);

        if (null === $entity) {
            throw new NotFoundHttpException(
                sprintf('Entity of type "%s" with ID "%d" not found', $entityClass, $id),
            );
        }

        if (!$entity instanceof ContentEditableInterface) {
            throw new NotFoundHttpException(
                sprintf('Entity of type "%s" must implement ContentEditableInterface', $entityClass),
            );
        }

        return $entity;
    }

    /**
     * Get all available blocks organized by tabs.
     *
     * @return array{blocks: array<string, array{block: BlockTypeInterface, type: string, tab: string, tabKey: string}>, tabs: array<string>}
     */
    public function getAvailableBlocks(): array
    {
        // Resolve the entity class from the resource name (validates interfaces)
        $entityClass = $this->resolveEntityClass($this->resourceName);

        // Load the entity
        $this->entity = $this->loadEntity($entityClass, $this->entityId);

        // Get all blocks from collection
        $allBlocks = $this->blockCollection->getBlocks();

        $blocks = [];
        $tabs = [];

        foreach ($allBlocks as $type => $block) {
            // Check if block supports this entity
            if (!$block->supports($this->entity)) {
                continue;
            }

            // Get block tab
            $tab = $block->getTab();
            $tabKey = str_replace(' ', '_', strtolower($tab));

            if (!in_array($tab, $tabs)) {
                $tabs[] = $tab;
            }

            $blocks[$type] = [
                'block' => $block,
                'type' => $type,
                'tab' => $tab,
                'tabKey' => $tabKey,
            ];
        }

        return [
            'blocks' => $blocks,
            'tabs' => $tabs,
        ];
    }

    /**
     * Toggle the block browser visibility.
     */
    #[LiveAction]
    public function toggleBlockBrowser(): void
    {
        $this->showBlockBrowser = !$this->showBlockBrowser;

        // Reset filters when opening
        if ($this->showBlockBrowser) {
            $this->blockFilterText = '';
            $this->blockFilterCategory = 'all_blocks';
        }
    }

    /**
     * Open the block browser.
     */
    #[LiveAction]
    public function openBlockBrowser(): void
    {
        $this->showBlockBrowser = true;
        $this->blockFilterText = '';
        $this->blockFilterCategory = 'all_blocks';
    }

    /**
     * Close the block browser.
     */
    #[LiveAction]
    public function closeBlockBrowser(): void
    {
        $this->showBlockBrowser = false;
        $this->blockFilterText = '';
        $this->blockFilterCategory = 'all_blocks';
    }

    /**
     * Get filtered blocks based on search text and category.
     *
     * @return array{blocks: array<string, array{block: BlockTypeInterface, type: string, tab: string, tabKey: string}>, tabs: array<string>}
     */
    public function getFilteredBlocks(): array
    {
        $allBlocksData = $this->getAvailableBlocks();

        // If no filters applied, return all blocks
        if (empty($this->blockFilterText) && $this->blockFilterCategory === 'all_blocks') {
            return $allBlocksData;
        }

        $filteredBlocks = [];
        $searchText = strtolower($this->blockFilterText);

        foreach ($allBlocksData['blocks'] as $type => $blockData) {
            $blockName = strtolower($blockData['block']->getName());
            $blockCategory = $blockData['tabKey'];

            // Check if matches text filter
            $matchesText = empty($searchText) || str_contains($blockName, $searchText);

            // Check if matches category filter
            $matchesCategory = $this->blockFilterCategory === 'all_blocks' || $blockCategory === $this->blockFilterCategory;

            if ($matchesText && $matchesCategory) {
                $filteredBlocks[$type] = $blockData;
            }
        }

        return [
            'blocks' => $filteredBlocks,
            'tabs' => $allBlocksData['tabs'],
        ];
    }

    /**
     * Add a new block of the specified type.
     */
    #[LiveAction]
    public function addBlock(#[LiveArg('blockType')] string $blockType): void
    {
        // Resolve the entity class from the resource name (validates interfaces)
        $entityClass = $this->resolveEntityClass($this->resourceName);

        // Load the entity
        $this->entity = $this->loadEntity($entityClass, $this->entityId);

        // Determine the ContentBlock class to use
        $contentBlockClass = null;

        // Try to get the class from an existing block
        $existingBlock = $this->entity->getContentBlocks()->first();
        if ($existingBlock) {
            $contentBlockClass = get_class($existingBlock);
        } else {
            // Fallback to the default ContentBlock class from this plugin
            $contentBlockClass = $this->entity->getContentBlockClass();

            // If the default class doesn't exist, throw an error
            if (!class_exists($contentBlockClass)) {
                throw new \RuntimeException('Cannot determine ContentBlock class. No existing blocks found and default class not available.');
            }
        }

        // Create a new content block
        $newBlock = new $contentBlockClass();

        if (!$newBlock instanceof ContentBlockInterface) {
            throw new \RuntimeException(
                sprintf('Block class "%s" must implement ContentBlockInterface', $contentBlockClass),
            );
        }

        // Get the next position for this locale
        $existingBlocks = $this->entity->getContentBlocksForPreview($this->locale);
        $nextPosition = $existingBlocks->count();

        // Configure the new block
        $newBlock->setLocale($this->locale);
        $newBlock->setType($blockType);
        $newBlock->setPosition($nextPosition);
        $newBlock->setPreviewPosition($nextPosition);
        $newBlock->setDraftData([]);
        $newBlock->setPublishedData([]);
        $newBlock->setPreviewPublishState(ThreeStateStatusEnum::PUBLISHED);

        // Add to entity
        $this->entity->addContentBlock($newBlock);
        $this->entityManager->persist($newBlock);
        $this->entityManager->flush();

        // Set the blockId to the newly created block so the editor opens it
        $this->blockId = $newBlock->getId();

        // Close the block browser
        $this->showBlockBrowser = false;

        // Dispatch event to reload iframe
        $this->dispatchBrowserEvent('block:added', [
            'blockId' => $this->blockId,
            'blockType' => $blockType,
        ]);

        // Dispatch event to reload iframe
        $this->dispatchBrowserEvent('block:saved', [
            'blockId' => $this->blockId,
        ]);
    }

    /**
     * Check if AI content generation is available
     */
    public function isAIGenerationAvailable(): bool
    {
        return $this->aiBundleDetector->isAvailable();
    }

    /**
     * Get AI installation instructions
     */
    public function getAIInstallationInstructions(): string
    {
        return $this->aiBundleDetector->getInstallationInstructions();
    }

    /**
     * Open the AI content generator
     */
    #[LiveAction]
    public function openAIGenerator(): void
    {
        $this->showAIGenerator = true;
        $this->aiPrompt = '';
        $this->aiBlockCount = 3;
    }

    /**
     * Close the AI content generator
     */
    #[LiveAction]
    public function closeAIGenerator(): void
    {
        $this->showAIGenerator = false;
        $this->aiPrompt = '';
        $this->aiBlockCount = 3;
    }

    /**
     * Generate content blocks using AI
     */
    #[LiveAction]
    public function generateAIContent(): void
    {
        // Validate inputs
        if (empty($this->aiPrompt)) {
            $this->errorMessage = 'Please provide a description for the content you want to generate.';
            return;
        }

        if ($this->aiBlockCount < 1 || $this->aiBlockCount > 10) {
            $this->errorMessage = 'Number of blocks must be between 1 and 10.';
            return;
        }

        try {
            // Resolve the entity class from the resource name
            $entityClass = $this->resolveEntityClass($this->resourceName);

            // Load the entity
            $this->entity = $this->loadEntity($entityClass, $this->entityId);

            // Generate blocks using AI
            $generatedBlocks = $this->blockContentGenerator->generateBlocks(
                $this->aiPrompt,
                $this->aiBlockCount,
            );

            // Determine the ContentBlock class to use
            $contentBlockClass = null;
            $existingBlock = $this->entity->getContentBlocks()->first();
            if ($existingBlock) {
                $contentBlockClass = get_class($existingBlock);
            } else {
                $contentBlockClass = $this->entity->getContentBlockClass();

                if (!class_exists($contentBlockClass)) {
                    throw new \RuntimeException('Cannot determine ContentBlock class.');
                }
            }

            // Get the current highest position for this locale
            $existingBlocks = $this->entity->getContentBlocksForPreview($this->locale);
            $nextPosition = $existingBlocks->count();

            // Create and persist each generated block
            $createdBlockIds = [];
            foreach ($generatedBlocks->getBlocks() as $index => $blockData) {
                $newBlock = new $contentBlockClass();

                if (!$newBlock instanceof ContentBlockInterface) {
                    throw new \RuntimeException(
                        sprintf('Block class "%s" must implement ContentBlockInterface', $contentBlockClass),
                    );
                }

                // Configure the new block
                $position = $nextPosition + $index;
                $newBlock->setLocale($this->locale);
                $newBlock->setType($blockData['block_type']);
                $newBlock->setPosition($position);
                $newBlock->setPreviewPosition($position);
                $newBlock->setDraftData($blockData['data']);
                $newBlock->setPublishedData($blockData['data']);
                $newBlock->setPreviewPublishState(
                    $blockData['block_published'] ? ThreeStateStatusEnum::PUBLISHED : ThreeStateStatusEnum::UNPUBLISHED,
                );

                // Add to entity
                $this->entity->addContentBlock($newBlock);
                $this->entityManager->persist($newBlock);

                $createdBlockIds[] = $newBlock->getId();
            }

            $this->entityManager->flush();

            // Close the AI generator
            $this->showAIGenerator = false;

            // Dispatch success event
            $this->dispatchBrowserEvent('ai:generation-success', [
                'blockCount' => $generatedBlocks->getCount(),
                'blockIds' => $createdBlockIds,
                'reload' => true,
            ]);

            // Dispatch success event
            $this->dispatchBrowserEvent('block-editor:reload-requested', []);
            $this->dispatchBrowserEvent('block-editor:close', []);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }
}
