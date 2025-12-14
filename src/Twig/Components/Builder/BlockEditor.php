<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Components\Builder;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Form\Block\EmptyBlockType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

class BlockEditor extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(writable: true)]
    public ?int $blockId = null;

    public function __construct(
        private readonly BlockCollection $blockCollection,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getBlock(): ?ContentBlockInterface
    {
        if (null === $this->blockId) {
            return null;
        }

        return $this->contentBlockRepository->find($this->blockId);
    }

    public function hasBlock(): bool
    {
        return null !== $this->getBlock();
    }

    protected function instantiateForm(): FormInterface
    {
        $block = $this->getBlock();

        if (null === $block) {
            return $this->createForm(EmptyBlockType::class, []);
        } else {
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
            $formClass = $blockConfig->getFormClass();

            // Use draft data for the form
            $draftData = $block->getDraftData() ?? [];
        }
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
        $block->setDraftData($formData);

        $this->entityManager->flush();

        // Dispatch event to reload iframe or show success message
        $this->dispatchBrowserEvent('block:saved', [
            'blockId' => $this->blockId,
        ]);
    }
}
