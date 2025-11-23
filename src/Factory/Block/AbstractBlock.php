<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\Block;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractBlock extends AbstractType implements BlockTypeInterface
{
    protected ?FormBuilderInterface $tempBuilder = null;

    /**
     * @var array
     * If a form type has been already treated, we don't need to
     * treat it again in the configureAdminFormThemes method
     */
    private array $treatedFormTypeThemes = [];

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected TranslatorInterface $translator,
        protected FormFactoryInterface $formFactory,
        protected Packages $packages,
    ) {
    }

    public function getManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function getPackages(): Packages
    {
        return $this->packages;
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
    abstract public function buildBlock(FormBuilderInterface $builder, array $options): void;

    private function setRootBuilder(): void
    {
        if (null === $this->tempBuilder) {
            $this->tempBuilder = $this->tempBuilder();
        }
    }

    private function tempBuilder(?string $class = null, ?string $name = null): FormBuilderInterface
    {
        $tempBuilder = $this->formFactory
            ->createNamedBuilder(
                $name ?? 'fake_builder',
                $class ?? static::class,
                null,
                [],
            );
        $this->buildBlock($tempBuilder, []);

        return $tempBuilder;
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
     * @return array{js?: array<string|Asset>|null, css?: array<string|Asset>|null, webpack?: array<string|Asset>|null}
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
     * @return array{js?: array<string|Asset>|null, css?: array<string|Asset>|null, webpack?: array<string|Asset>|null}
     */
    public function configureAdminAssets(): array
    {
        $this->setRootBuilder();

        /**
         * @var array{js: array<string|Asset>|null, css: array<string|Asset>|null, webpack: array<string|Asset>|null} $adminAssets
         */
        $adminAssets = [
            'js' => [],
            'css' => [],
            'webpack' => [],
        ];
        if (null !== $this->tempBuilder) {
            foreach ($this->tempBuilder->getForm() as $child) {
                $formTypeClass = get_class($child->getConfig()->getType()->getInnerType());
                if (method_exists($formTypeClass, 'configureAdminAssets')) {
                    $assets = call_user_func([$formTypeClass, 'configureAdminAssets']);
                    if (is_array($assets)) {
                        /** @var array{js: array<string|Asset>|null, css: array<string|Asset>|null, webpack: array<string|Asset>|null} $mergedAssets */
                        $mergedAssets = array_merge_recursive($adminAssets, $assets);
                        $adminAssets = $mergedAssets;
                    }
                }
            }
        }

        return $adminAssets;
    }

    /**
     * Declare here the form themes path that make back-office form display as expected
     *
     * @return string[]
     */
    public function configureAdminFormThemes(): array
    {
        $this->setRootBuilder();
        if (null !== $this->tempBuilder) {
            return $this->getAdminFormThemesRecursive($this->tempBuilder);
        }

        return [];
    }

    /**
     * @return string[]
     */
    public static function researchableProperties(): array
    {
        return [];
    }

    public function getTab(): string
    {
        return $this->translator->trans('sylius_happy_cms.editor.blocks');
    }

    public function getPosition(): int
    {
        return 100;
    }

    public function supports(?ResourceInterface $resource = null): bool
    {
        return true;
    }

    private function getAdminFormThemesRecursive(FormBuilderInterface $builder, array $adminFormThemes = []): array
    {
        if (!$builder->getCompound()) {
            $formTypeClass = $builder->getType()->getInnerType()::class;
            $this->treatedFormTypeThemes[$formTypeClass] = $formTypeClass;

            return $this->getAdminFormThemes($formTypeClass, $adminFormThemes);
        }

        foreach ($builder->getForm() as $child) {
            $formTypeClass = $child->getConfig()->getType()->getInnerType()::class;
            $adminFormThemes = $this->getAdminFormThemes($formTypeClass, $adminFormThemes);
            $this->treatedFormTypeThemes[$formTypeClass] = $formTypeClass;

            $isCollection = is_subclass_of($formTypeClass, CollectionType::class);
            if (!$isCollection) {
                continue;
            }

            /** @var class-string|null $innerType */
            $innerType = $child->getConfig()->getOption('entry_type');
            if (
                null !== $innerType &&
                (
                    !in_array($innerType, array_keys($this->treatedFormTypeThemes)) ||
                    (isset($this->treatedFormTypeThemes[$innerType]) && $this->treatedFormTypeThemes[$innerType] !==
                        $formTypeClass)
                ) && is_subclass_of($innerType, BlockTypeInterface::class)
            ) {
                $this->treatedFormTypeThemes[$innerType] = $formTypeClass;
                $tempBuilder = $this->tempBuilder($innerType, (string) (time() + rand(1, 100)));
                $adminFormThemes = $this->getAdminFormThemesRecursive($tempBuilder, $adminFormThemes);
            }
        }

        return $adminFormThemes;
    }

    private function getAdminFormThemes(string $formTypeClass, array $adminFormThemes): array
    {
        if (method_exists($formTypeClass, 'configureAdminFormThemes')) {
            $callable = [$formTypeClass, 'configureAdminFormThemes'];
            if (is_callable($callable)) {
                $formThemes = call_user_func($callable);
                if (is_array($formThemes)) {
                    $adminFormThemes = array_merge($adminFormThemes, $formThemes);
                }
            }
        }

        return $adminFormThemes;
    }
}
