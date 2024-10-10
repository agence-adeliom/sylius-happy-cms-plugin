<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Menu;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\EnumField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
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
        // Si le menu n'est pas défini, récupérer le menu du menuItem parent
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event) {
            /** @var MenuItemInterface $menuItem */
            $menuItem = $event->getData();
            $menuItemPositions = $menuItem->getParent()->getChildren()
                ->filter(fn (MenuItemInterface $mi): bool => $mi !== $menuItem)
                ->map(fn (MenuItemInterface $menuItem): ?int => $menuItem->getPosition())
                ->toArray();
            $newPosition = [] !== $menuItemPositions ? max($menuItemPositions) + 1 : 0;
            $menuItem->setPosition($newPosition);

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
            $newMenuItem = Action::new('menu_items.new', 'sylius_happy_cms.menu_item.admin.action.create', 'plus')
                ->linkToRoute('sylius_happy_cms_admin_menu_item_create', ['id' => $menuId])
                ->addCssClass('primary');
            $actions->addGlobalAction(Crud::PAGE_INDEX, $newMenuItem);

            $actions->remove(Crud::PAGE_EDIT, Action::INDEX);
        }

        return $actions;
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        $menuId = $this->getMenuId();

        yield TabField::new('menu', 'sylius_happy_cms.menu_item.admin.tab.menu_item');

        if ($menuId && $this->locator->has('parameter_bag')) {
            yield Field::new('menu', 'sylius_happy_cms.menu_item.admin.field.menu')
                ->onlyOnForms()
                ->setFormType(EntityType::class)
                ->setFormTypeOption('class', $this->locator->get('parameter_bag')->get('happy_cms_menu.menu.class'))
                ->setFormTypeOption('placeholder', false)
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

        if ($menuId && ($resource instanceof MenuItemInterface && null !== $resource->getId())) {
            $parentField
                ->setFormTypeOption(
                    'query_builder',
                    fn (EntityRepository $er): QueryBuilder => $er->createQueryBuilder('mi')
                    ->andWhere('mi.id != :id')
                    ->andWhere('mi.menu = :menuId')
                    ->setParameter('id', $resource->getId())
                    ->setParameter('menuId', $menuId),
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

        yield Field::new('target', 'sylius_happy_cms.menu_item.admin.field.target');

        yield Field::new('position', 'sylius_happy_cms.menu_item.admin.field.position');

        yield EnumField::new('publishState', 'sylius_happy_cms.menu_item.admin.field.state')
            ->setEnum(ThreeStateStatusEnum::class)
            ->hideOnIndex()
            ->setFormTypeOption('placeholder', false)
            ->renderExpanded();

        yield TabField::new('link', 'sylius_happy_cms.menu_item.admin.tab.link');

        yield TranslationField::new('translations', 'sylius_happy_cms.menu_item.admin.field.translations')
            ->addField(Field::new('name', 'sylius_happy_cms.menu_item.admin.field.name'))
            ->addField(Field::new('url', 'sylius_happy_cms.menu_item.admin.field.url'))
            ->hideOnIndex();
    }

    private function getMenuId(): ?int
    {
        $resource = $this->getResource();

        if ($resource instanceof MenuItemInterface && null !== $resource->getMenu()) {
            return $resource->getMenu()->getId();
        }

        $menuId = (int) $this->getResourceFieldValueInRequest(formName: 'menu_item_admin', fieldName: 'menu');

        return $menuId ?: null;
    }
}
