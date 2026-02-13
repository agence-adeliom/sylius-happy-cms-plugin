<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\PageBuilder;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\AssetDto;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockCollection;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageBuilderController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $parameterBag,
        private readonly LocaleProviderInterface $localeProvider,
        private readonly BlockCollection $blockCollection,
        private readonly TranslatorInterface $translator,
        private readonly SharedBlockCollection $sharedBlockCollection,
    ) {
    }

    public function builderAction(Request $request, string $resource, int $id): Response
    {
        // Resolve the entity class from the resource name (validates interfaces)
        $entityClass = $this->resolveEntityClass($resource);

        // Load the entity
        $entity = $this->loadEntity($entityClass, $id);

        // Check if we need to publish content
        if ($request->query->has('publish') && '1' === $request->query->get('publish')) {

            /** @var string[] $localesToPublish */
            $localesToPublish = $request->query->all('locales');

            if (!empty($localesToPublish)) {
                $this->publishContent($entity, $localesToPublish);

                $this->addFlash('success', $this->translator->trans('sylius_happy_cms.page_builder.content_published_successfully'));

                // Redirect to remove the publish parameter from URL
                return $this->redirectToRoute('sylius_happy_cms_admin_page_builder', [
                    'resource' => $resource,
                    'id' => $id,
                    'locale' => $request->query->get('locale', $request->getLocale()),
                ]);
            }
        }

        // Get the current locale from query parameter or use default locale
        $locale = $request->query->get('locale', $request->getLocale());

        // Ensure the locale is a string
        if (!is_string($locale)) {
            $locale = $this->localeProvider->getDefaultLocaleCode();
        }

        // Get available locales
        $availableLocales = $this->localeProvider->getAvailableLocalesCodes();

        // Validate that the selected locale is available
        if (!in_array($locale, $availableLocales, true)) {
            $locale = $request->getLocale();
        }

        // Get all content blocks for the entity (already validated as ContentEditableInterface)
        // In preview mode, we show all blocks (published and unpublished) for the given locale
        $contentBlocks = $entity->getContentBlocksForPreview($locale);

        // Generate back route to edit page
        $backRoute = $this->generateBackRoute($resource);

        return $this->render('@SyliusHappyCMSPlugin/admin/page_builder/index.html.twig', [
            'entity' => $entity,
            'resource' => $resource,
            'contentBlocks' => $contentBlocks,
            'locale' => $locale,
            'availableLocales' => $availableLocales,
            'backRoute' => $backRoute,
        ]);
    }

    /**
     * Generate the back route name from the resource key.
     * Tries to find the update route for the given resource.
     */
    private function generateBackRoute(string $resource): ?string
    {
        // Get Sylius resources configuration
        $resources = $this->parameterBag->get('sylius.resources');

        if (!is_array($resources) || !isset($resources[$resource])) {
            return null;
        }

        $resourceConfig = $resources[$resource];

        // Try to get the route prefix from resource configuration
        // Sylius routes usually follow the pattern: {prefix}_{resource_name}_{action}
        // For example: sylius_admin_page_update, sylius_shop_page_show

        // Extract the resource name (part after the last dot)
        // Example: sylius_happy_cms.page => page
        $resourceParts = explode('.', $resource);
        $resourceName = end($resourceParts);

        // Build possible route names (try most common patterns)
        $possibleRoutes = [
            'app_' . $resourceName . '_update',  // Standard Sylius admin pattern
        ];

        // If the resource has a driver prefix, try that too
        if (count($resourceParts) > 1) {
            $prefix = $resourceParts[0];
            $possibleRoutes[] = $prefix . '_admin_' . $resourceName . '_update';
        }

        // Try each possible route name and return the first one that exists
        foreach ($possibleRoutes as $routeName) {
            try {
                $this->generateUrl($routeName, ['id' => 1]); // Test route exists

                return $routeName;
            } catch (\Exception $e) {
                // Route doesn't exist, try next one
                continue;
            }
        }

        return null;
    }

    public function blockEditAction(Request $request, string $resource, int $id, int $blockId): Response
    {
        // Resolve the entity class from the resource name (validates interfaces)
        $entityClass = $this->resolveEntityClass($resource);

        // Load the entity
        $entity = $this->loadEntity($entityClass, $id);

        // Get the current locale from query parameter or use default locale
        $locale = $request->query->get('locale', $request->getLocale());

        // Get all shared blocks
        $this->getSharedBlockList($entity);

        // Load the block
        $block = $this->loadBlock($entity, $blockId, $locale);

        // Ensure the locale is a string
        if (!is_string($locale)) {
            $locale = $this->localeProvider->getDefaultLocaleCode();
        }

        $formChanged = false;

        // Get the block type configuration
        $blockType = $block->getType();
        if (null === $blockType) {
            throw new \LogicException('Block has no type');
        }

        $blocks = $this->blockCollection->getBlocks();
        if (!isset($blocks[$blockType])) {
            throw new \LogicException(sprintf('Unknown block type: %s', $blockType));
        }

        $blockConfig = $blocks[$blockType];
        $formClass = $blockConfig::class;

        // Get form themes from the block type
        $formThemes = ['@SyliusAdmin/shared/form_theme.html.twig'];
        if (method_exists($blockConfig, 'configureAdminFormThemes')) {
            $formThemes = array_values(
                array_unique(
                    array_merge($formThemes, $blockConfig->configureAdminFormThemes()),
                ),
            );
        }

        // Get block name
        $className = (new \ReflectionClass($formClass))->getShortName();
        $blockName = trim(preg_replace('/([A-Z])/', ' $1', str_replace('BlockType', '', $className)) ?: '');

        // Use draft data for the form, fallback to published data if draft is empty
        $draftData = $block->getDraftData();
        if (null === $draftData || empty($draftData)) {
            $draftData = $block->getPublishedData() ?? [];
        }

        // Create the form
        $form = $this->createForm($formClass, $draftData, [
            'action' => $this->generateUrl('sylius_happy_cms_admin_page_builder_block_edit', [
                'resource' => $resource,
                'id' => $id,
                'blockId' => $blockId,
                'locale' => $locale,
            ]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get form data
            /**
             * @var array{
             *     block_type?: string,
             *     block_published?: bool,
             * } $formData
             */
            $formData = $form->getData();

            // Remove metadata fields if they exist
            if (isset($formData['block_type'])) {
                unset($formData['block_type']);
            }
            if (isset($formData['block_published'])) {
                unset($formData['block_published']);
            }

            // Save to draft data
            $block->setDraftData($formData);

            // Persist changes to database
            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans('sylius_happy_cms.page_builder.block_saved_successfully'));
            $formChanged = true;
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('warning', $this->translator->trans('sylius_happy_cms.page_builder.form_contain_errors'));
        }

        // Get block position and total blocks for the toolbar
        $contentBlocks = $entity->getContentBlocksForPreview($locale);
        $blockPosition = 0;
        $totalBlocks = count($contentBlocks);

        foreach ($contentBlocks as $index => $cb) {
            if ($cb->getId() === $blockId) {
                $blockPosition = $index;

                break;
            }
        }

        // Collect assets from the block being edited
        $assets = $this->collectBlockAssets([$block]);

        return $this->render('@SyliusHappyCMSPlugin/admin/page_builder/block_edit.html.twig', [
            'form' => $form->createView(),
            'formThemes' => $formThemes,
            'block' => $block,
            'blockName' => $blockName,
            'blockPosition' => $blockPosition,
            'totalBlocks' => $totalBlocks,
            'resource' => $resource,
            'entityId' => $id,
            'blockId' => $blockId,
            'locale' => $locale,
            'blockAssets' => $assets,
            'sendMessageToParent' => $formChanged,
        ]);
    }

    private function getSharedBlockList(ContentEditableInterface $entity): void
    {
        global $allowedSharedBlockTypesForResource;
        if ($entity instanceof ResourceInterface && empty($allowedSharedBlockTypesForResource)) {
            // Get all shared allowed blocks type for current resource
            // Then put as global variable to be used in sub files (shared block type)
            $sharedBlocksCollection = $this->sharedBlockCollection->enabledSupportFilter();
            $sharedBlocks = $sharedBlocksCollection->getAllowedBlocks(
                $entity,
            );
            $allowedSharedBlockTypesForResource = array_keys($sharedBlocks);
        }
    }

    /**
     * Collect all assets from all blocks in the page.
     *
     * @param iterable<ContentBlockInterface> $contentBlocks
     *
     * @return array{css: array<string|AssetDto>, js: array<string|AssetDto>, webpack: array<string|AssetDto>}
     */
    private function collectBlockAssets(iterable $contentBlocks): array
    {
        $assets = [
            'css' => [],
            'js' => [],
            'webpack' => [],
        ];

        $blocks = $this->blockCollection->getBlocks();

        foreach ($contentBlocks as $contentBlock) {
            $blockType = $contentBlock->getType();
            if (null === $blockType || !isset($blocks[$blockType])) {
                continue;
            }

            $blockConfig = $blocks[$blockType];

            // Get assets from the block type
            if (method_exists($blockConfig, 'configureAdminAssets')) {
                $blockAssets = $blockConfig->configureAdminAssets();

                // Merge CSS assets
                if (isset($blockAssets['css'])) {
                    foreach ($blockAssets['css'] as $asset) {
                        // Use asset value as key to avoid duplicates
                        $key = is_object($asset) && method_exists($asset, 'getValue') ? $asset->getValue() : (string) $asset;
                        $assets['css'][$key] = is_object($asset) && method_exists($asset, 'getAsDto') ? $asset->getAsDto() : (string) $asset;
                    }
                }

                // Merge JS assets
                if (isset($blockAssets['js'])) {
                    foreach ($blockAssets['js'] as $asset) {
                        $key = is_object($asset) && method_exists($asset, 'getValue') ? $asset->getValue() : (string) $asset;
                        $assets['js'][$key] = is_object($asset) && method_exists($asset, 'getAsDto') ? $asset->getAsDto() : (string) $asset;
                    }
                }

                // Merge Webpack assets
                if (isset($blockAssets['webpack'])) {
                    foreach ($blockAssets['webpack'] as $asset) {
                        $key = is_object($asset) && method_exists($asset, 'getValue') ? $asset->getValue() : (string) $asset;
                        $assets['webpack'][$key] = is_object($asset) && method_exists($asset, 'getAsDto') ? $asset->getAsDto() : (string) $asset;
                    }
                }
            }
        }

        return $assets;
    }

    /**
     * Publish content for the specified locales.
     * Copies draft data to published data for all blocks in the selected locales.
     * Also permanently deletes blocks that are marked as deleted.
     *
     * @param array<string> $locales
     */
    private function publishContent(ContentEditableInterface $entity, array $locales): void
    {
        $allBlocks = $entity->getContentBlocks();

        $publishedCount = 0;
        $blocksToDelete = [];

        foreach ($allBlocks as $block) {
            // Check if block belongs to one of the selected locales
            if (in_array($block->getLocale(), $locales, true)) {
                // Check if block is marked as deleted - schedule for permanent deletion
                if ($block->isDeleted()) {
                    $blocksToDelete[] = $block;

                    continue;
                }

                $draftData = $block->getDraftData();

                // Only publish if there's draft data
                if (null !== $draftData) {
                    // Copy draft data to published data
                    $block->setPublishedData($draftData);

                    // Copy preview position to published state
                    $block->setPosition($block->getPreviewPosition());

                    // Copy preview state to published state
                    $block->setPublishState($block->getPreviewPublishState());

                    // Copy preview published date to published data
                    $block->setPublishDate($block->getPreviewPublishDate());

                    // Copy preview published date to published data
                    $block->setUnpublishDate($block->getPreviewUnpublishDate());
                }
            }
        }

        // Permanently delete blocks marked as deleted
        foreach ($blocksToDelete as $block) {
            $entity->removeContentBlock($block);
            $this->entityManager->remove($block);
        }

        // Flush changes to database
        $this->entityManager->flush();
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
     * Load a content block by its ID from an entity.
     */
    private function loadBlock(ContentEditableInterface $entity, int $blockId, string $locale): ContentBlockInterface
    {
        $blocks = $entity->getContentBlocksForPreview($locale);

        $block = $blocks->filter(function (ContentBlockInterface $block) use ($blockId) {
            return $block->getId() === $blockId;
        })->first();

        if (false === $block) {
            throw new NotFoundHttpException(
                sprintf('Block with ID "%d" not found', $blockId),
            );
        }

        return $block;
    }
}
