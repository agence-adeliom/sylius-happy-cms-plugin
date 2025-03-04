<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Page;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ColumnField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\EnumField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ResourceAutocompleteChoiceField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\SlugField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusEasyCrudPlugin\Enum\ColumnSizeEnum;
use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\FlexibleContentField;
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\SEOField;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Sylius\Bundle\GridBundle\Builder\Filter\StringFilter;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

abstract class AbstractPageAdmin extends AbstractAdmin implements PageAdminInterface
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_page_admin';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'name';
    }

    public function configureFilters(): iterable
    {
        yield StringFilter::create('name', ['translations.name'])
            ->setLabel('sylius_happy_cms.page.admin.field.name');
    }

    public function configureActions(string $pageName): Actions
    {
        $actions = parent::configureActions($pageName);

        $locales = $this->getSyliusLocales();

        $contentAction = Action::new('content', 'sylius_happy_cms.page.admin.action.manage_content', 'flag outline');
        foreach ($locales as $locale) {
            $contentAction->addSubAction(
                Action::new($locale->getCode(), $locale->getCode(), 'flag outline')
                    ->linkToRoute('sylius_happy_cms_admin_page_update', [
                        'context' => 'flexible_content:' . $locale->getCode(),
                    ]),
            );
        }

        $actions->addItemAction(Crud::PAGE_INDEX, $contentAction);
        $actions->addItemAction(Crud::PAGE_DETAIL, $contentAction);
        $actions->addItemAction(Crud::PAGE_EDIT, $contentAction);

        return $actions;
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        if (null === $context) {
            yield TabField::new('Page', 'sylius_happy_cms.page.admin.tab.page');

            yield ResourceAutocompleteChoiceField::new('parent', 'sylius_happy_cms.page.admin.field.parent')
                ->setMultiple(false)
                ->setEmptyData('1')
                ->setResource('sylius_happy_cms.page');

            yield Field::new('name', 'sylius_happy_cms.page.admin.field.name')
                ->setSortablePath('translations.name')
                ->onlyOnIndex();

            yield Field::new('slug', 'sylius_happy_cms.page.admin.field.slug')
                ->setSortablePath('translations.slug')
                ->onlyOnIndex();

            yield ColumnField::new('sylius_happy_cms.page.admin.panel.metadatas')
                ->setSize(ColumnSizeEnum::WIDE_8_OF_16);

            yield TranslationField::new('translations', 'sylius_happy_cms.page.admin.field.translations')
                ->addField(
                    Field::new('name')
                        ->setFormTypeOption('constraints', [
                            //new NotBlank(),
                        ]),
                )
                ->addField(
                    SlugField::new('slug')
                        ->setLabel('sylius_happy_cms.page.admin.field.slug')
                        ->setFormTypeOption('constraints', [
                            //new NotBlank(),
                        ]),
                )
                ->hideOnIndex();

            yield ColumnField::new('sylius_happy_cms.page.admin.panel.publication')
                ->setSize(ColumnSizeEnum::WIDE_8_OF_16);

            yield EnumField::new('publishState', 'sylius_happy_cms.page.admin.field.state')
                ->setEnum(ThreeStateStatusEnum::class)
                ->hideOnIndex()
                ->renderExpanded(true);

            yield TabField::new('seo', 'sylius_happy_cms.page.admin.tab.seo');

            yield TranslationField::new('seoTranslations', 'sylius_happy_cms.page.admin.field.seo.translations')
                ->addField(
                    SEOField::new('seo', 'sylius_happy_cms.page.admin.field.seo')
                        ->setDisabled(false)
                        ->setRequired(true)
                        ->setFormTypeOption('constraints', [
//                        new Length(['min' => 1])
                        ]),
                )
                ->hideOnIndex();
        } elseif (str_starts_with($context, 'flexible_content:')) {
            $locale = str_replace('flexible_content:', '', $context);
            yield TranslationField::new('translations')
                ->restrictToLocales([
                    $locale,
                ])
                ->addField(
                    FlexibleContentField::new('content')
                        ->hideOnIndex(),
                )
                ->hideOnIndex();
        }
    }
}
