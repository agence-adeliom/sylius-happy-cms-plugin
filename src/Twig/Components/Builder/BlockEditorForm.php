<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Components\Builder;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

class BlockEditorForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public int $blockId;

    #[LiveProp(writable: true)]
    public string $resourceName;

    #[LiveProp(writable: true)]
    public int $entityId;

    #[LiveProp(writable: true)]
    public string $locale;

    private ?ContentEditableInterface $entity = null;
    private ?ContentBlockInterface $block = null;

    /** @var string[] */
    private array $formThemes = [];

    /** Track if form has been initialized to prevent overwriting user data */
    #[LiveProp]
    public bool $isFormInitialized = false;

    public function __construct(
        private readonly BlockCollection $blockCollection,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    public function getBlock(): ?ContentBlockInterface
    {
        if (null !== $this->block) {
            return $this->block;
        }

        // Resolve the entity class from the resource name (validates interfaces)
        $entityClass = $this->resolveEntityClass($this->resourceName);

        // Load the entity
        $this->entity = $this->loadEntity($entityClass, $this->entityId);

        $blocks = $this->entity->getContentBlocks();

        $this->block = $blocks->filter(function (ContentBlockInterface $block) {
            return $block->getId() === $this->blockId;
        })->first();

        return $this->block;
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
        return trim(preg_replace('/([A-Z])/', ' $1', str_replace('BlockType', '', $className)));
    }

    /**
     * Get form themes for the current block type.
     *
     * @return string[]
     */
    public function getFormThemes(): array
    {
        return array_values(
            array_unique(
                array_merge(
                    ['@SyliusAdmin/shared/form_theme.html.twig'],
                    $this->formThemes,
                ),
            ),
        );
    }

    protected function instantiateForm(): FormInterface
    {
        $block = $this->getBlock();

        if (null === $block) {
            throw new \LogicException('BlockEditorForm requires a valid block');
        }

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
        if (method_exists($blockConfig, 'configureAdminFormThemes')) {
            $this->formThemes = $blockConfig->configureAdminFormThemes();
        } else {
            $this->formThemes = [];
        }

        // Use draft data for the form, fallback to published data if draft is empty
        $draftData = $block->getDraftData();

        // If draft data is null or empty, use published data as fallback
        if (null === $draftData || empty($draftData)) {
            $draftData = $block->getPublishedData() ?? [];
        }

        // Only set initial values on first load, not on subsequent re-renders
        // This prevents overwriting user modifications
        if (!$this->isFormInitialized) {
            $this->formValues = $draftData;
            $this->isFormInitialized = true;
        }

        // Create and return the form without passing data (handled by ComponentWithFormTrait via formValues)
        // Disable CSRF protection as Live Components have their own security mechanism
        return $this->createForm($formClass, $draftData, [
            'csrf_protection' => false,
        ]);
    }

    #[LiveAction]
    public function save(): void
    {
        // Submit the form
        $this->submitForm();

        // Get the form instance
        $form = $this->getForm();

        // Check if the form is valid
        if (!$form->isValid()) {
            // If form is not valid, the component will re-render with errors
            return;
        }

        // Get the block
        $block = $this->getBlock();
        if (null === $block) {
            return;
        }

        // IMPORTANT: Use $form->getData() instead of $this->formValues
        // because $this->formValues doesn't capture values controlled by JavaScript (like Vue.js v-model in MediaType)
        /** @var array<string, mixed> $formData */
        $formData = $form->getData();

        // Save to draft data
        $block->setDraftData($formData);

        // Persist changes to database
        $this->entityManager->flush();

        // Dispatch event to reload iframe to show the updated block
        $this->dispatchBrowserEvent('block:saved', [
            'blockId' => $this->blockId,
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
}
