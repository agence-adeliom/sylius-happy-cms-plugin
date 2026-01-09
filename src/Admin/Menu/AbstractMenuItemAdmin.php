<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Menu;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ColumnField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\EnumField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusEasyCrudPlugin\Enum\ColumnSizeEnum;
use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItem;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\GridBundle\Builder\GridBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

abstract class AbstractMenuItemAdmin extends AbstractAdmin implements MenuItemAdminInterface
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_menu_item_admin';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'name';
    }

    public function buildGrid(GridBuilderInterface $gridBuilder): void
    {
        parent::buildGrid($gridBuilder);

        $menuId = $this->getMenuId();
        if ($menuId) {
            $gridBuilder->setDriverOption('repository', [
                'method' => 'filterByMenu',
                'arguments' => [
                    $menuId,
                    "expr:service('sylius.context.locale').getLocaleCode()",
                ],
            ]);
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event) {
            /** @var MenuItemInterface $menuItem */
            $menuItem = $event->getData();

            /**
             * We don't want to change the position if it's an existing menu, only
             * if we're creating a new menu.
             * */
            if ($menuItem->getId() === null) {
                if (null !== $menuItem->getParent()) {
                    $menuItemPositions = $menuItem->getParent()->getChildren()
                        ->filter(fn (MenuItemInterface $mi): bool => $mi !== $menuItem)
                        ->map(fn (MenuItemInterface $menuItem): ?int => $menuItem->getPosition())
                        ->toArray();
                    $newPosition = [] !== $menuItemPositions ? max($menuItemPositions) + 1 : 0;
                    $menuItem->setPosition($newPosition);
                } else {
                    $menuItem->setPosition(0);
                }
            }

            if (null === $menuItem->getMenu()) {
                $menuItem->setMenu($menuItem->getParent()?->getMenu());
            }
        });
    }

    public function configureActions(string $pageName): Actions
    {
        $actions = parent::configureActions($pageName);
        $menuId = null;
        if ($this->getMenuId()) {
            $menuId = $this->getMenuId();
        } elseif ($this->getResource() instanceof MenuItemInterface) {
            $menuId = $this->getResource()->getMenu()?->getId();
        }

        if ($menuId) {
            $actions->remove(Crud::PAGE_INDEX, Action::NEW);
            $newMenuItem = Action::new('menu_items.new', 'sylius_happy_cms.menu_item.admin.action.create', 'bxs:plus')
                ->linkToRoute('sylius_happy_cms_admin_menu_item_create', ['menu_id' => $menuId])
                ->addCssClass('primary');
            $actions->addGlobalAction(Crud::PAGE_INDEX, $newMenuItem);
        }

        return $actions;
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        $menuId = $this->getMenuId();

        yield TabField::new('menu', 'sylius_happy_cms.menu_item.admin.tab.menu_item')
            ->renderHorizontal();

        yield EnumField::new('publishState', 'sylius_happy_cms.menu_item.admin.field.state')
            ->setEnum(ThreeStateStatusEnum::class)
            ->hideOnIndex()
            ->setRequired(false)
            ->setFormTypeOption('placeholder', false)
            ->renderExpanded();

        yield TranslationField::new('translations', 'sylius_happy_cms.menu_item.admin.field.translations')
            ->addField(Field::new('name', 'sylius_happy_cms.menu_item.admin.field.name'))
            ->addField(Field::new('url', 'sylius_happy_cms.menu_item.admin.field.url'))
            ->hideOnIndex();

        yield Field::new('target', 'sylius_happy_cms.menu_item.admin.field.target');
        //yield Field::new('position', 'sylius_happy_cms.menu_item.admin.field.position');

        yield ColumnField::new('col2', '')
            ->setSize(ColumnSizeEnum::WIDE_6_OF_12);
        //yield TabField::new('menu', 'sylius_happy_cms.menu_item.admin.tab.menu_item')
        //    ->renderHorizontal();

        /** @var array<string, array{
         *  classes: array{
         *     model: class-string,
         *     controller: class-string,
         *     repository: class-string,
         *     form: class-string,
         *     factory: class-string,
         *  }
         * }|null> $syliusResources */
        $syliusResources = $this->crudAdminFactory->parameterBag->get('sylius.resources');
        if ($menuId && is_array($syliusResources['sylius_happy_cms.menu']) && is_array($syliusResources['sylius_happy_cms.menu']['classes'])) {
            yield Field::new('menu', 'sylius_happy_cms.menu_item.admin.field.menu')
                ->onlyOnForms()
                ->setFormType(EntityType::class)
                ->setFormTypeOption('class', $syliusResources['sylius_happy_cms.menu']['classes']['model'])
                ->setFormTypeOption('placeholder', false)
                ->setFormTypeOption('attr', ['disabled' => 'disabled'])
                ->setFormTypeOption(
                    'query_builder',
                    fn (EntityRepository $er): QueryBuilder => $er->createQueryBuilder('m')
                    ->andWhere('m.id = :id')
                    ->setParameter('id', $menuId),
                )
            ;
        }

        $parentField = Field::new('parent', 'sylius_happy_cms.menu_item.admin.field.parent')
            ->setFormTypeOption('choice_label', fn (MenuItem $choice): string => $choice->getFlattenParents())
            ->setGridTemplatePath('@SyliusHappyCMSPlugin/field/menu/grid_menu_item_parent.html.twig');

        $resource = $this->getResource();

        if ($menuId && ($resource instanceof MenuItemInterface)) {
            $parentField
                ->setFormTypeOption(
                    'query_builder',
                    function (EntityRepository $er) use ($resource, $menuId): QueryBuilder {
                        $builder = $er->createQueryBuilder('mi');
                        $builder
                            ->andWhere('mi.menu = :menuId')
                            ->setParameter('menuId', $menuId);
                        if (null !== $resource->getId()) {
                            $builder
                                ->andWhere('mi.id != :id')
                                ->setParameter('id', $resource->getId());
                        }

                        return $builder;
                    },
                );
        }

        yield $parentField;

        yield Field::new('name', 'sylius_happy_cms.menu_item.admin.field.name')
            ->setSortablePath('translations.name')
            ->onlyOnIndex();

        //        // TODO fix exception "Can't get a way to read the property "url" in class "App\Entity\EasyMenu\MenuItem"."
        //        yield Field::new('url', 'sylius_happy_cms.menu_item.admin.field.url')
        //            ->setSortablePath('translations.url')
        //            ->onlyOnIndex();
    }

    private function getMenuId(): ?int
    {
        $resource = $this->getResource();
        $request = $this->crudAdminFactory->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        if ($resource instanceof MenuItemInterface && null !== $resource->getMenu()) {
            $request->attributes->set('menu_id', $resource->getMenu()->getId());

            return $resource->getMenu()->getId();
        }

        $menuId = (int) $this->getResourceFieldValueInRequest(formName: 'menu_item_admin', fieldName: 'menu');
        $request->attributes->set('menu_id', $menuId ?: null);

        return $menuId ?: null;
    }
}
