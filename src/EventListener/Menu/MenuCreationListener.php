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
        /** @var array<string, array{
         *  classes: array{
         *     model: class-string,
         *     controller: class-string,
         *     repository: class-string,
         *     form: class-string,
         *     factory: class-string,
         *  }
         * }|null> $resources */
        $resources = $this->parameterBag->get('sylius.resources');
        if (!is_array($resources) || !is_array($resources['sylius_happy_cms.menu_item'])) {
            return;
        }

        if (!is_array($resources['sylius_happy_cms.menu_item']['classes'])) {
            return;
        }

        $modelClass = $resources['sylius_happy_cms.menu_item']['classes']['model']
            ?? null;

        if (!is_string($modelClass) || !class_exists($modelClass)) {
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
