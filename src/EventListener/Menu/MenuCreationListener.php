<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener\Menu;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemTranslationInterface;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MenuCreationListener
{
    public function __construct(
        protected LocaleProviderInterface $localeProvider,
        protected TranslatorInterface $translator,
    ) {
    }

    // the entity listener methods receive two arguments:
    // the entity instance and the lifecycle event
    public function prePersist(MenuInterface $menu): void
    {
        // TODO : adjust to create menu item
        ///**
        // * @var MenuItemInterface $rootItem
        // */
        //$rootItem = new $this->menuItemClass();
        //$rootItem->setMenu($menu);
        //$rootItem->setPublishState(ThreeStateStatusEnum::PUBLISHED()->getValue());
        //$rootItem->setPosition(0);
        //
        //$menu->addItem($rootItem);
        //
        //foreach ($this->localeProvider->getAvailableLocalesCodes() as $locale) {
        //    if (class_exists($this->menuItemClass) && method_exists($this->menuItemClass, 'getTranslationClass')) {
        //        $menuItemTranslationClass = $this->menuItemClass::getTranslationClass();
        //        $translation = new $menuItemTranslationClass();
        //        if ($translation instanceof MenuItemTranslationInterface) {
        //            $translation->setLocale($locale);
        //            $translation->setName(
        //                $this->translator->trans('sylius_happy_cms.menu_item.admin.data.menu_item_root', locale: $locale),
        //            );
        //            $rootItem->addTranslation($translation);
        //        }
        //    }
        //}
    }
}
