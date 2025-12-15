<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Components\Builder;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Form\Block\EmptyBlockType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

class BlockEditor extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true, onUpdated: 'getBlock')]
    public ?int $blockId = null;

    #[LiveProp(writable: true)]
    public string $resourceName;

    #[LiveProp(writable: true)]
    public int $entityId;

    public ContentEditableInterface $entity;

    public function __construct(
        private readonly BlockCollection $blockCollection,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $parameterBag,

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
        })->first();;
    }

    public function hasBlock(): bool
    {
        return null !== $this->getBlock();
    }

    protected function instantiateForm(): FormInterface
    {
        $block = $this->getBlock();

        if (null === $block) {
            // Create mode: return empty form (will show "Browse Blocks" button)
            return $this->createForm(EmptyBlockType::class, []);
        }

        // Edit mode: Get the block type configuration
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

        // Use draft data for the form
        $draftData = $block->getDraftData() ?? [];

        // Create and return the form with draft data
        return $this->createForm($formClass, $draftData);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->submitForm();

        $block = $this->getBlock();
        if (null === $block) {
            return;
        }

        /** @var array<string, mixed> $formData */
        $formData = $this->getForm()->getData();

        // Save to draft data
        //$block->setDraftData($formData);

        //$this->entityManager->flush();

        // Dispatch event to reload iframe or show success message
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
