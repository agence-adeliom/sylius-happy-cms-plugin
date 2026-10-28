<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener\Menu;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemTranslationInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Creates the root item of every new menu; with `$assignRootItem` (`sylius_happy_cms.menu.root_item.assign: true`),
 * makes it the menu's root (`Menu#rootItem`) and leaves a menu persisted with its own root alone.
 */
class MenuCreationListener
{
    public const DEFAULT_ROOT_ITEM_LABEL = 'sylius_happy_cms.menu_item.admin.data.menu_item_root';

    /**
     * @param bool                 $assignRootItem  false (deprecated): the root item is created but `Menu#rootItem`
     *                                              stays null, and a menu persisted with its own root gets a second
     *                                              item without parent
     * @param ManagerRegistry|null $managerRegistry to take the root item class from the Doctrine mapping of
     *                                              `Menu#rootItem` when there is no `sylius_happy_cms.menu_item` resource
     * @param string               $rootItemLabel   translated in each available locale; a text that is not a
     *                                              translation key is kept as is
     */
    public function __construct(
        protected LocaleProviderInterface $localeProvider,
        protected TranslatorInterface $translator,
        protected ParameterBagInterface $parameterBag,
        protected bool $assignRootItem = false,
        protected ?ManagerRegistry $managerRegistry = null,
        protected string $rootItemLabel = self::DEFAULT_ROOT_ITEM_LABEL,
    ) {
    }

    // the entity listener methods receive two arguments:
    // the entity instance and the lifecycle event
    public function prePersist(MenuInterface $menu): void
    {
        // The project created the root item itself.
        if ($this->assignRootItem && null !== $menu->getRootItem()) {
            return;
        }

        $modelClass = $this->resolveRootItemClass($menu);
        if (null === $modelClass) {
            return;
        }

        /** @var MenuItemInterface $rootItem */
        $rootItem = new $modelClass();
        $rootItem->setMenu($menu);
        $rootItem->setPublishState(ThreeStateStatusEnum::PUBLISHED);
        $rootItem->setPosition(0);

        $menu->addItem($rootItem);
        if ($this->assignRootItem) {
            $menu->setRootItem($rootItem);
        }

        if (!method_exists($modelClass, 'getTranslationClass')) {
            return;
        }

        foreach ($this->localeProvider->getAvailableLocalesCodes() as $locale) {
            $menuItemTranslationClass = $modelClass::getTranslationClass();
            $translation = new $menuItemTranslationClass();
            if ($translation instanceof MenuItemTranslationInterface) {
                $translation->setLocale($locale);
                $translation->setName(
                    $this->translator->trans($this->rootItemLabel, locale: $locale),
                );
                $rootItem->addTranslation($translation);
            }
        }
    }

    /**
     * The model of the `sylius_happy_cms.menu_item` resource; without this resource (a project naming its menu item
     * resource differently), the class Doctrine maps on `Menu#rootItem` (`resolve_target_entities` applied).
     *
     * @return class-string|null
     */
    protected function resolveRootItemClass(MenuInterface $menu): ?string
    {
        $resources = $this->parameterBag->has('sylius.resources') ? $this->parameterBag->get('sylius.resources') : null;
        $resource = is_array($resources) ? ($resources['sylius_happy_cms.menu_item'] ?? null) : null;

        if (null !== $resource) {
            $classes = is_array($resource) ? ($resource['classes'] ?? null) : null;
            $modelClass = is_array($classes) ? ($classes['model'] ?? null) : null;

            return is_string($modelClass) && class_exists($modelClass) ? $modelClass : null;
        }

        $metadata = $this->managerRegistry?->getManagerForClass($menu::class)?->getClassMetadata($menu::class);
        if (null === $metadata || !$metadata->hasAssociation('rootItem')) {
            return null;
        }

        $modelClass = $metadata->getAssociationTargetClass('rootItem');

        return null !== $modelClass && is_a($modelClass, MenuItemInterface::class, true) ? $modelClass : null;
    }
}
