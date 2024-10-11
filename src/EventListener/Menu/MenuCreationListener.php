<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener\Menu;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemTranslationInterface;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MenuCreationListener
{
    public function __construct(
        protected LocaleProviderInterface $localeProvider,
        protected TranslatorInterface $translator,
        protected ParameterBagInterface $parameterBag,
    ) {
    }

    // the entity listener methods receive two arguments:
    // the entity instance and the lifecycle event
    public function prePersist(MenuInterface $menu): void
    {
        $modelClass = $this->parameterBag->get('sylius.resources')['sylius_happy_cms.menu_item']['classes']['model']
            ?? null;

        if (!class_exists($modelClass)) {
            return;
        }

        /** @var MenuItemInterface $rootItem */
        $rootItem = new $modelClass();
        $rootItem->setMenu($menu);
        $rootItem->setPublishState(ThreeStateStatusEnum::PUBLISHED()->getValue());
        $rootItem->setPosition(0);

        $menu->addItem($rootItem);

        foreach ($this->localeProvider->getAvailableLocalesCodes() as $locale) {
            if (class_exists($modelClass) && method_exists($modelClass, 'getTranslationClass')) {
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
    }
}
