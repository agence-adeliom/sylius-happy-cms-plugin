<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractSharedBlockType extends AbstractType implements SharedBlockTypeInterface
{
    public function __construct(protected EntityManagerInterface $manager)
    {
    }

    public function getManager(): EntityManagerInterface
    {
        return $this->manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('block_type', HiddenType::class, ['data' => $this::class])
            ->add('block_published', HiddenType::class)
            ->add('position', HiddenType::class)
        ;

        $this->buildBlock($builder, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attr = [];
        $attr['block-title'] = $this->getName();
        $attr['block-icon'] = is_iterable($this->getIcon()) ? $this->getIcon()[0] : $this->getIcon();
        $view->vars['attr'] = $attr;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'cascade_validation' => true,
        ]);
    }

    /**
     * Declare here the assets that make front working as expected
     *
     * @return array<string, string[]>
     */
    public function configureAssets(): array
    {
        return [
            'js' => [],
            'css' => [],
            'webpack' => [],
        ];
    }

    /**
     * Declare here the assets that make back-office working as expected
     *
     * @return array<string, string[]>
     */
    public function configureAdminAssets(): array
    {
        return [
            'js' => [],
            'css' => [],
        ];
    }

    /**
     * Declare here the form themes path that make back-office form display as expected
     *
     * @return string[]
     */
    public function configureAdminFormThemes(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public static function researchableProperties(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public static function getDefaultSettings(): array
    {
        return [];
    }

    public function getPosition(): int
    {
        return 100;
    }

    public function supports(?ContentEditableInterface $resource = null): bool
    {
        return true;
    }
}
