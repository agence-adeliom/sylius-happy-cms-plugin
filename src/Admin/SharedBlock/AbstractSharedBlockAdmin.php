<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\SharedBlock;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\SharedBlockField;
use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlockInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class AbstractSharedBlockAdmin extends AbstractAdmin implements ServiceSubscriberInterface, AdminInterface
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_shared_block_admin';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'key';
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        /** @var SharedBlockInterface|null $block */
        $block = $this->getResource();
        yield TabField::new('configuration', 'sylius_happy_cms.shared_block.admin.tab.configuration')
            ->renderHorizontal();

        yield Field::new('name', 'sylius_happy_cms.shared_block.admin.field.name')
            ->setRequired(true);

        yield Field::new('key', 'sylius_happy_cms.shared_block.admin.field.key')
            ->setRequired(true);

        $blockType = $block?->getType() ?: $this->getResourceFieldValueInRequest(formName: 'shared_block_admin', fieldName: 'type', queryKey: 'block_type');

        yield Field::new('type', 'sylius_happy_cms.shared_block.admin.field.type')
            ->setFormType(null === $blockType ? TextType::class : HiddenType::class)
            ->setFormTypeOption('data', $blockType)
            ->setRequired(true);

        yield Field::new('status', 'sylius_happy_cms.shared_block.admin.field.status');

        if (null !== $blockType) {
            yield TabField::new('tabContent', 'sylius_happy_cms.shared_block.admin.tab.content');
            yield TranslationField::new('translations', 'sylius_happy_cms.shared_block.admin.field.translations')
                ->addField(
                    SharedBlockField::new('content', 'sylius_happy_cms.shared_block.admin.field.content')
                        ->setBlockType($blockType)
                        ->setDisabled(false)
                        ->setRequired(true),
                )
                ->hideOnIndex();
        }
    }

    public function configureActions(string $pageName): Actions
    {
        $actions = parent::configureActions($pageName);
        $actions->remove(Crud::PAGE_INDEX, Action::NEW);
        $actions->addGlobalAction(
            Crud::PAGE_INDEX,
            Action::new('shared_block.select', 'sylius_happy_cms.shared_block.admin.action.create', 'plus')
                ->linkToRoute('sylius_happy_cms_admin_shared_block_select')
                ->addCssClass('primary'),
        );

        return $actions;
    }
}
