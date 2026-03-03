# Menu System - AI Guide

This guide helps AI agents understand and work with the menu management system in Sylius Happy CMS Plugin.

## Overview

The plugin provides a flexible hierarchical menu system with:
- Unlimited nested levels
- Multi-language support
- Publish state control
- Custom attributes (CSS class, target, position)
- Template-based rendering

## Data Model

### Menu

**Location**: `src/Entity/Menu/Menu.php`

Main container for menu items.

**Key Properties**:
```php
- code: string          // Unique identifier (e.g., "main", "footer")
- name: string          // Display name for admin
- status: bool          // Active/inactive
- items: Collection     // Menu items
- rootItem: MenuItem    // Root item (managed automatically)
```

### MenuItem

**Location**: `src/Entity/Menu/MenuItem.php`

Hierarchical menu item using Gedmo Tree (nested set).

**Key Properties**:
```php
- menu: Menu                    // Parent menu
- parent: MenuItem|null         // Parent item (null for root)
- children: Collection          // Child items
- translations: Collection      // Translations
- publishState: enum            // Published/Unpublished/Pending
- classAttribute: string|null   // CSS class
- position: int|null            // Display order
- target: bool|null             // Open in new window
- lft, rgt, lvl, root: int      // Nested set structure (managed by Gedmo)
```

### MenuItemTranslation

**Location**: `src/Entity/Menu/MenuItemTranslation.php`

Translated content for menu items.

**Key Properties**:
```php
- name: string      // Display text
- url: string       // Link destination
- locale: string    // Language code
```

## Creating Menus Programmatically

### Basic Menu Creation

```php
use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;

// 1. Create menu
$menu = new Menu();
$menu->setCode('main');
$menu->setName('Main Navigation');
$menu->setStatus(true);
$em->persist($menu);
$em->flush();

// 2. Create menu item
$item = new MenuItem();
$item->setMenu($menu);
$item->setPosition(0);
$item->setPublishState(ThreeStateStatusEnum::PUBLISHED);

// 3. Add translation
$translation = new MenuItemTranslation();
$translation->setName('Home');
$translation->setUrl('/en_US');
$translation->setLocale('en_US');
$item->addTranslation($translation);

$em->persist($item);
$em->flush();
```

### Hierarchical Menu Creation

```php
// Create parent item
$parentItem = new MenuItem();
$parentItem->setMenu($menu);
$parentItem->setPosition(0);
$parentItem->setPublishState(ThreeStateStatusEnum::PUBLISHED);

$parentTranslation = new MenuItemTranslation();
$parentTranslation->setName('Services');
$parentTranslation->setUrl('/en_US/services');
$parentTranslation->setLocale('en_US');
$parentItem->addTranslation($parentTranslation);

$em->persist($parentItem);

// Create child item
$childItem = new MenuItem();
$childItem->setMenu($menu);
$childItem->setParent($parentItem);  // Set parent relationship
$childItem->setPosition(0);
$childItem->setPublishState(ThreeStateStatusEnum::PUBLISHED);

$childTranslation = new MenuItemTranslation();
$childTranslation->setName('Consulting');
$childTranslation->setUrl('/en_US/services/consulting');
$childTranslation->setLocale('en_US');
$childItem->addTranslation($childTranslation);

$em->persist($childItem);
$em->flush();
```

### Batch Menu Creation

```php
function createMenuStructure(
    array $items,
    ?MenuItem $parent,
    Menu $menu,
    string $locale,
    EntityManagerInterface $em
): void {
    foreach ($items as $index => $itemData) {
        // Create item
        $menuItem = new MenuItem();
        $menuItem->setMenu($menu);
        $menuItem->setParent($parent);
        $menuItem->setPosition($index);
        $menuItem->setPublishState(ThreeStateStatusEnum::PUBLISHED);

        // Optional: Set custom class
        if (isset($itemData['class'])) {
            $menuItem->setClassAttribute($itemData['class']);
        }

        // Optional: Set target
        if (isset($itemData['target'])) {
            $menuItem->setTarget($itemData['target']);
        }

        // Add translation
        $translation = new MenuItemTranslation();
        $translation->setName($itemData['name']);
        $translation->setUrl($itemData['url'] ?? '#');
        $translation->setLocale($locale);
        $menuItem->addTranslation($translation);

        $em->persist($menuItem);

        // Recursively create children
        if (isset($itemData['children'])) {
            createMenuStructure($itemData['children'], $menuItem, $menu, $locale, $em);
        }
    }
}

// Usage
$structure = [
    [
        'name' => 'Home',
        'url' => '/en_US',
    ],
    [
        'name' => 'Services',
        'url' => '/en_US/services',
        'class' => 'has-dropdown',
        'children' => [
            ['name' => 'Consulting', 'url' => '/en_US/services/consulting'],
            ['name' => 'Development', 'url' => '/en_US/services/development'],
        ],
    ],
];

createMenuStructure($structure, null, $menu, 'en_US', $em);
$em->flush();
```

## Rendering Menus

### Twig Function

**Extension**: `src/Twig/Menu/MenuExtension.php`

**Basic usage**:
```twig
{{ happy_cms_menu('main') }}
```

**With custom template**:
```twig
{{ happy_cms_menu('main', {
    template: '@App/menus/custom.html.twig'
}) }}
```

**With extra variables**:
```twig
{{ happy_cms_menu('main', {
    template: '@App/menus/custom.html.twig',
    cssClass: 'navbar-menu'
}) }}
```

### Default Template Location

Templates must be at:
```
templates/bundles/SyliusHappyCMSPlugin/front/menus/{menu_code}.html.twig
```

For menu with code "main":
```
templates/bundles/SyliusHappyCMSPlugin/front/menus/main.html.twig
```

### Template Structure

**Basic template**:
```twig
<nav>
    <ul>
        {% if menu.rootItem %}
            {% for item in menu.rootItem.publishedChildren %}
                <li class="{{ item.classAttribute }}">
                    <a href="{{ item.translation.url }}"
                       {% if item.target %}target="_blank"{% endif %}>
                        {{ item.translation.name }}
                    </a>
                </li>
            {% endfor %}
        {% endif %}
    </ul>
</nav>
```

**With nested items**:
```twig
<nav>
    <ul class="menu">
        {% if menu.rootItem %}
            {% for item in menu.rootItem.publishedChildren %}
                <li class="menu-item {{ item.classAttribute }}">
                    <a href="{{ item.translation.url }}"
                       {% if item.target %}target="_blank"{% endif %}>
                        {{ item.translation.name }}
                    </a>

                    {% if item.hasChild() %}
                        <ul class="submenu">
                            {% for child in item.publishedChildren %}
                                <li class="submenu-item">
                                    <a href="{{ child.translation.url }}"
                                       {% if child.target %}target="_blank"{% endif %}>
                                        {{ child.translation.name }}
                                    </a>
                                </li>
                            {% endfor %}
                        </ul>
                    {% endif %}
                </li>
            {% endfor %}
        {% endif %}
    </ul>
</nav>
```

**Recursive template** (unlimited depth):
```twig
{% macro render_items(items) %}
    <ul>
        {% for item in items %}
            <li class="{{ item.classAttribute }}">
                <a href="{{ item.translation.url }}"
                   {% if item.target %}target="_blank"{% endif %}>
                    {{ item.translation.name }}
                </a>

                {% if item.hasChild() and item.publishedChildren|length > 0 %}
                    {{ _self.render_items(item.publishedChildren) }}
                {% endif %}
            </li>
        {% endfor %}
    </ul>
{% endmacro %}

<nav class="menu">
    {% if menu.rootItem %}
        {{ _self.render_items(menu.rootItem.publishedChildren) }}
    {% endif %}
</nav>
```

## Command Example

Create a console command to generate menus:

```php
<?php

namespace App\Command;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemTranslationInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:menu:create')]
class CreateMenuCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $manager,
        private string $menuClass,
        private string $menuItemClass,
        private string $menuItemTranslationClass,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Create menu
        $menu = new $this->menuClass();
        $menu->setCode('main');
        $menu->setName('Main Navigation');
        $menu->setStatus(true);

        $this->manager->persist($menu);
        $this->manager->flush();

        // Define structure
        $items = [
            ['name' => 'Home', 'url' => '/en_US'],
            [
                'name' => 'About',
                'url' => '/en_US/about',
                'children' => [
                    ['name' => 'Team', 'url' => '/en_US/about/team'],
                    ['name' => 'History', 'url' => '/en_US/about/history'],
                ],
            ],
        ];

        $this->createItems($items, null, $menu, 'en_US');
        $this->manager->flush();

        $output->writeln('Menu created!');
        return Command::SUCCESS;
    }

    private function createItems(
        array $items,
        ?MenuItemInterface $parent,
        MenuInterface $menu,
        string $locale
    ): void {
        foreach ($items as $index => $data) {
            $item = new $this->menuItemClass();
            $item->setMenu($menu);
            $item->setParent($parent);
            $item->setPosition($index);
            $item->setPublishState(ThreeStateStatusEnum::PUBLISHED);

            $trans = new $this->menuItemTranslationClass();
            $trans->setName($data['name']);
            $trans->setUrl($data['url']);
            $trans->setLocale($locale);
            $item->addTranslation($trans);

            $this->manager->persist($item);

            if (isset($data['children'])) {
                $this->createItems($data['children'], $item, $menu, $locale);
            }
        }
    }
}
```

**Service registration**:
```yaml
# config/services.yaml
App\Command\CreateMenuCommand:
    arguments:
        $menuClass: '%sylius.model.sylius_happy_cms.menu.class%'
        $menuItemClass: '%sylius.model.sylius_happy_cms.menu_item.class%'
        $menuItemTranslationClass: '%sylius.model.sylius_happy_cms.menu_item_translation.class%'
    tags: ['console.command']
```

## Key Methods Reference

### Menu Methods

```php
// Menu management
$menu->getCode(): string
$menu->getName(): ?string
$menu->isStatus(): bool
$menu->getItems(): Collection
$menu->getRootItem(): ?MenuItemInterface
$menu->addItem(MenuItemInterface $item): void
```

### MenuItem Methods

```php
// Basic properties
$item->getName(): ?string
$item->getTranslation(?string $locale): MenuItemTranslation
$item->getPublishState(): ThreeStateStatusEnum
$item->getClassAttribute(): ?string
$item->getPosition(): ?int
$item->isTarget(): ?bool

// Hierarchy
$item->getParent(): ?MenuItemInterface
$item->getChildren(): Collection
$item->getPublishedChildren(): Collection
$item->hasParent(): bool
$item->hasChild(): bool
$item->getParents(): array
$item->getFlattenParents(): string

// Management
$item->setParent(?MenuItemInterface $parent): void
$item->addChild(MenuItemInterface $child): void
```

### MenuItemTranslation Methods

```php
$translation->getName(): ?string
$translation->getUrl(): ?string
$translation->getLocale(): string
$translation->setName(?string $name): void
$translation->setUrl(?string $url): void
```

## Performance Optimization: ESI

For high-traffic sites, render menus with ESI (Edge Side Includes):

### Enable ESI

```yaml
# config/packages/framework.yaml
framework:
    esi: { enabled: true }
    fragments: { path: /_fragment }
```

### Controller

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends AbstractController
{
    public function render(string $code): Response
    {
        $menu = $this->em->getRepository(MenuInterface::class)
            ->findOneByCode($code);

        $response = $this->render('@SyliusHappyCMSPlugin/front/menus/main.html.twig', [
            'menu' => $menu,
        ]);

        // Cache for 1 hour
        $response->setSharedMaxAge(3600);

        return $response;
    }
}
```

### Template

```twig
{{ render_esi(controller('App\\Controller\\MenuController::render', {
    code: 'main'
})) }}
```

## Demo Command

Generate sample menus:

```bash
php bin/console happycms:starter:create-demo-menu
```

**Creates**:
- Main navigation menu
- Footer menu
- Example hierarchical structure

**Source**: `src/Command/Starter/CreateDemoMenuCommand.php`

## Common Patterns

### Menu with Active State

```twig
{% set currentPath = app.request.pathinfo %}

<nav>
    <ul>
        {% if menu.rootItem %}
            {% for item in menu.rootItem.publishedChildren %}
                <li class="{{ item.classAttribute }} {% if currentPath starts with item.translation.url %}active{% endif %}">
                    <a href="{{ item.translation.url }}">
                        {{ item.translation.name }}
                    </a>
                </li>
            {% endfor %}
        {% endif %}
    </ul>
</nav>
```

### Menu with Icons

Add icon field to MenuItemTranslation or use class attribute:

```twig
<nav>
    <ul>
        {% if menu.rootItem %}
            {% for item in menu.rootItem.publishedChildren %}
                <li>
                    <a href="{{ item.translation.url }}">
                        <i class="icon {{ item.classAttribute }}"></i>
                        {{ item.translation.name }}
                    </a>
                </li>
            {% endfor %}
        {% endif %}
    </ul>
</nav>
```

### Mega Menu

```twig
<nav class="mega-menu">
    {% if menu.rootItem %}
        {% for item in menu.rootItem.publishedChildren %}
            <div class="menu-section">
                <h3>{{ item.translation.name }}</h3>
                {% if item.hasChild() %}
                    <div class="columns">
                        {% for child in item.publishedChildren %}
                            <div class="column">
                                <a href="{{ child.translation.url }}">
                                    {{ child.translation.name }}
                                </a>
                            </div>
                        {% endfor %}
                    </div>
                {% endif %}
            </div>
        {% endfor %}
    {% endif %}
</nav>
```

## Troubleshooting

### Menu Not Rendering

**Check**:
1. Menu exists: `findOneByCode($code)`
2. Menu is active: `$menu->isStatus()`
3. Items are published: Use `getPublishedChildren()`
4. Template exists at correct path
5. Clear cache: `php bin/console cache:clear`

### Template Not Found

**Error**: `TemplateNotFoundException`

**Solutions**:
1. Create template at: `templates/bundles/SyliusHappyCMSPlugin/front/menus/{code}.html.twig`
2. Or specify custom template:
```twig
{{ happy_cms_menu('main', {template: '@App/menus/custom.html.twig'}) }}
```

### Wrong Hierarchy

**Check**:
1. Parent relationships: `$item->getParent()`
2. Nested set values: `$item->getLft()`, `$item->getRgt()`, `$item->getLvl()`
3. Use `getParents()` to debug hierarchy
4. Rebuild tree if corrupted (Gedmo Tree extension)

### Translation Missing

**Check**:
1. Translation exists: `$item->getTranslation($locale)`
2. Locale is set: `$item->setCurrentLocale($locale)`
3. Fallback to default locale if needed

## Best Practices

1. **Unique codes**: Use descriptive codes ("header", "footer", not "menu1", "menu2")
2. **Publish states**: Unpublish items instead of deleting
3. **Positions**: Use incremental values (0, 10, 20) for easy reordering
4. **Depth limit**: Keep menu depth reasonable (3-4 levels max)
5. **Cache**: Use ESI for frequently accessed menus
6. **Translations**: Ensure all active locales have translations
7. **Root items**: Don't create root items manually (managed automatically)
8. **Performance**: Use `getPublishedChildren()` instead of filtering all children

## Database Schema

**Tables**:
- `sylius_..._menu` - Menu entities
- `sylius_..._menu_item` - Menu items with nested set
- `sylius_..._menu_item_translation` - Translations

**Key columns** (MenuItem):
- `lft`, `rgt`, `lvl`, `root` - Nested set structure (Gedmo)
- `parent_id` - Parent item reference
- `menu_id` - Menu reference
- `position` - Display order
- `class_attribute` - CSS class
- `target` - New window flag
- `publish_state` - Published/Unpublished/Pending
