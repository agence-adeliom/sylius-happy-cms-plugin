<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener\Menu;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemTranslationInterface;
use Doctrine\ORM\Mapping\MappedSuperclass;
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
        $modelClass = $this->rootItemClass();
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

    /**
     * The class of the root item: the model of the `sylius_happy_cms.menu_item` resource, or else the
     * `menu.menu_item.menu_item_model` setting. The plugin's default model is a mapped superclass that cannot be saved:
     * no root item then, as without the resource before.
     *
     * @return class-string<MenuItemInterface>|null
     */
    protected function rootItemClass(): ?string
    {
        /** @var array<string, array{classes?: array{model?: mixed}}|null>|null $resources */
        $resources = $this->parameterBag->has('sylius.resources') ? $this->parameterBag->get('sylius.resources') : null;
        $modelClass = $resources['sylius_happy_cms.menu_item']['classes']['model'] ?? null;

        if (null === $modelClass && $this->parameterBag->has('sylius_happy_cms.menu.menu_item.menu_item_model')) {
            $modelClass = $this->parameterBag->get('sylius_happy_cms.menu.menu_item.menu_item_model');
            if (is_string($modelClass) && class_exists($modelClass) && [] !== (new \ReflectionClass($modelClass))->getAttributes(MappedSuperclass::class)) {
                return null;
            }
        }

        if (!is_string($modelClass) || !class_exists($modelClass) || !is_a($modelClass, MenuItemInterface::class, true)) {
            return null;
        }

        return $modelClass;
    }
}
