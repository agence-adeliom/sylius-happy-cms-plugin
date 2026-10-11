<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener\Menu;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemTranslationInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Creates the root item of every new menu and makes it the menu's root (`Menu#rootItem`).
 */
class MenuCreationListener
{
    public function __construct(
        protected LocaleProviderInterface $localeProvider,
        protected TranslatorInterface $translator,
        protected ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * @param LifecycleEventArgs<\Doctrine\Persistence\ObjectManager>|null $args
     */
    public function prePersist(MenuInterface $menu, ?LifecycleEventArgs $args = null): void
    {
        // The project created the root item itself.
        if (null !== $menu->getRootItem()) {
            return;
        }

        $modelClass = $this->getMenuItemClass($menu, $args);
        if (null === $modelClass) {
            return;
        }

        /** @var MenuItemInterface $rootItem */
        $rootItem = new $modelClass();
        $rootItem->setMenu($menu);
        $rootItem->setPublishState(ThreeStateStatusEnum::PUBLISHED);
        $rootItem->setPosition(0);

        $menu->addItem($rootItem);
        $menu->setRootItem($rootItem);

        if (!method_exists($modelClass, 'getTranslationClass')) {
            return;
        }

        foreach ($this->localeProvider->getAvailableLocalesCodes() as $locale) {
            $menuItemTranslationClass = $modelClass::getTranslationClass();
            $translation = new $menuItemTranslationClass();
            if ($translation instanceof MenuItemTranslationInterface) {
                $translation->setLocale($locale);
                $translation->setName(
                    $this->translator->trans('sylius_happy_cms.menu_item.admin.data.menu_item_root', locale: $locale),
                );
                $rootItem->addTranslation($translation);
            }
        }
    }

    /**
     * The class Doctrine maps on `Menu#rootItem` (`resolve_target_entities` applied), whatever the name of the Sylius
     * resource of the menu items; the `sylius_happy_cms.menu_item` resource model when called without event arguments.
     *
     * @param LifecycleEventArgs<\Doctrine\Persistence\ObjectManager>|null $args
     *
     * @return class-string<MenuItemInterface>|null
     */
    protected function getMenuItemClass(MenuInterface $menu, ?LifecycleEventArgs $args): ?string
    {
        $modelClass = null;

        if (null !== $args) {
            $metadata = $args->getObjectManager()->getClassMetadata($menu::class);
            if ($metadata->hasAssociation('rootItem')) {
                $modelClass = $metadata->getAssociationTargetClass('rootItem');
            }
        } elseif ($this->parameterBag->has('sylius.resources')) {
            $resources = $this->parameterBag->get('sylius.resources');
            if (is_array($resources) &&
                is_array($resources['sylius_happy_cms.menu_item'] ?? null) &&
                is_array($resources['sylius_happy_cms.menu_item']['classes'] ?? null)) {
                $modelClass = $resources['sylius_happy_cms.menu_item']['classes']['model'] ?? null;
            }
        }

        if (!is_string($modelClass) || !is_a($modelClass, MenuItemInterface::class, true)) {
            return null;
        }

        return $modelClass;
    }
}
