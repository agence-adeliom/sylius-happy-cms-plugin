<div align="center">

# Menu System

</div>

## Overview

The Sylius Happy CMS Plugin provides a flexible menu management system that allows you to create hierarchical navigation menus with multi-language support. Menus are managed through the admin interface and can be easily rendered in your templates.

## Key Features

- **Hierarchical structure** - Unlimited nested menu items
- **Multi-language support** - Translated menu items for each locale
- **Publish state** - Control visibility of menu items
- **Custom attributes** - CSS classes, target, position
- **Template flexibility** - Custom templates per menu
- **Nested Set** - Efficient hierarchical queries using Gedmo Tree

## Data Model

### Menu

The main menu container with a unique code identifier.

**Entity**: `Adeliom\SyliusHappyCMSPlugin\Entity\Menu\Menu`

**Properties**:
- `id` - Unique identifier
- `code` - Unique code (e.g., "main", "footer")
- `name` - Display name
- `status` - Active/inactive status
- `items` - Collection of menu items
- `rootItem` - Root menu item (parent of all items)
- `createdAt`, `updatedAt` - Timestamps

### MenuItem

Hierarchical menu items organized in a tree structure.

**Entity**: `Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItem`

**Properties**:
- `id` - Unique identifier
- `menu` - Parent menu reference
- `parent` - Parent menu item (null for root)
- `children` - Collection of child items
- `translations` - Menu item translations
- `publishState` - Published/unpublished/pending
- `classAttribute` - CSS class for styling
- `position` - Display order
- `target` - Open in new window (true/false)
- `lft`, `rgt`, `lvl`, `root` - Nested set tree structure (managed by Gedmo)
- `publishedAt`, `unpublishedAt` - Publication dates
- `createdAt`, `updatedAt` - Timestamps

### MenuItemTranslation

Translated content for menu items.

**Entity**: `Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemTranslation`

**Properties**:
- `id` - Unique identifier
- `name` - Display name in specific language
- `url` - Link URL
- `locale` - Language code (e.g., "en_US", "fr_FR")

## Managing Menus in Admin

### Creating a Menu

1. Go to **CMS → Menus** in the admin panel
2. Click **Create**
3. Fill in:
   - **Code**: Unique identifier (e.g., "main", "footer")
   - **Name**: Display name for admin
   - **Status**: Enable/disable menu
4. Save

### Adding Menu Items

1. Open your menu
2. Click **Add Item**
3. Configure:
   - **Name**: Display text (per language)
   - **URL**: Link destination
   - **Parent**: Select parent item for nesting
   - **CSS Class**: Custom CSS class
   - **Target**: Open in new window
   - **Position**: Display order
   - **Publish State**: Published/Unpublished/Pending
4. Save

### Hierarchical Structure

Create nested menus by setting parent items:

```
Main Menu
├── Home
├── Services
│   ├── Consulting
│   ├── Development
│   └── Design
├── Blog
└── Contact
```

## Rendering Menus in Templates

### Basic Usage

Use the `happy_cms_menu()` Twig function:

```twig
{{ happy_cms_menu('main') }}
```

This renders the menu with code "main" using the default template.

### Custom Template

Specify a custom template:

```twig
{{ happy_cms_menu('main', {
    template: '@App/menus/custom_menu.html.twig'
}) }}
```

### Default Template Location

By default, menus use templates located at:
```
templates/bundles/SyliusHappyCMSPlugin/front/menus/{menu_code}.html.twig
```

For a menu with code "main":
```
templates/bundles/SyliusHappyCMSPlugin/front/menus/main.html.twig
```

### Template Example

Create a custom menu template:

**File**: `templates/bundles/SyliusHappyCMSPlugin/front/menus/main.html.twig`

```twig
<nav class="main-navigation">
    <ul class="menu">
        {% if menu.rootItem %}
            {% for item in menu.rootItem.publishedChildren %}
                <li class="menu-item {{ item.classAttribute }}">
                    <a href="{{ item.translation.url }}"
                       {% if item.target %}target="_blank"{% endif %}>
                        {{ item.translation.name }}
                    </a>

                    {# Render sub-menu if has children #}
                    {% if item.hasChild() %}
                        <ul class="sub-menu">
                            {% for child in item.publishedChildren %}
                                <li class="sub-menu-item {{ child.classAttribute }}">
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

### Advanced Template with Recursion

For deeply nested menus:

```twig
{% macro render_menu_items(items) %}
    <ul>
        {% for item in items %}
            <li class="{{ item.classAttribute }}">
                <a href="{{ item.translation.url }}"
                   {% if item.target %}target="_blank"{% endif %}>
                    {{ item.translation.name }}
                </a>

                {% if item.hasChild() and item.publishedChildren|length > 0 %}
                    {{ _self.render_menu_items(item.publishedChildren) }}
                {% endif %}
            </li>
        {% endfor %}
    </ul>
{% endmacro %}

<nav class="menu">
    {% if menu.rootItem %}
        {{ _self.render_menu_items(menu.rootItem.publishedChildren) }}
    {% endif %}
</nav>
```

## Creating Menus Programmatically

### Using Command

Create a command to generate menus:

```php
<?php

namespace App\Command;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemTranslationInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateMenuCommand extends Command
{
    protected static $defaultName = 'app:create-menu';

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

        // Create menu structure
        $items = [
            [
                'name' => 'Home',
                'url' => '/en_US',
            ],
            [
                'name' => 'Services',
                'url' => '/en_US/services',
                'children' => [
                    ['name' => 'Consulting', 'url' => '/en_US/services/consulting'],
                    ['name' => 'Development', 'url' => '/en_US/services/development'],
                ],
            ],
            [
                'name' => 'Contact',
                'url' => '/en_US/contact',
            ],
        ];

        $this->createMenuItems($items, null, $menu, 'en_US');
        $this->manager->flush();

        $output->writeln('Menu created successfully!');
        return Command::SUCCESS;
    }

    private function createMenuItems(
        array $items,
        ?MenuItemInterface $parent,
        MenuInterface $menu,
        string $locale,
    ): void {
        foreach ($items as $index => $itemData) {
            // Create menu item
            $menuItem = new $this->menuItemClass();
            $menuItem->setMenu($menu);
            $menuItem->setParent($parent);
            $menuItem->setPosition($index);
            $menuItem->setPublishState(ThreeStateStatusEnum::PUBLISHED);

            // Create translation
            $translation = new $this->menuItemTranslationClass();
            $translation->setName($itemData['name']);
            $translation->setUrl($itemData['url'] ?? '#');
            $translation->setLocale($locale);

            $menuItem->addTranslation($translation);
            $this->manager->persist($menuItem);

            // Create children recursively
            if (isset($itemData['children'])) {
                $this->createMenuItems($itemData['children'], $menuItem, $menu, $locale);
            }
        }
    }
}
```

### Register Command

```yaml
# config/services.yaml
services:
    App\Command\CreateMenuCommand:
        arguments:
            $menuClass: '%sylius.model.sylius_happy_cms.menu.class%'
            $menuItemClass: '%sylius.model.sylius_happy_cms.menu_item.class%'
            $menuItemTranslationClass: '%sylius.model.sylius_happy_cms.menu_item_translation.class%'
        tags: ['console.command']
```

### Run Command

```bash
php bin/console app:create-menu
```

## Performance Optimization with ESI

For better performance, use Edge Side Includes (ESI) to cache menu rendering separately from the main page.

### Enable ESI

**Framework configuration:**

```yaml
# config/packages/framework.yaml
framework:
    esi: { enabled: true }
    fragments: { path: /_fragment }
```

### Render with ESI

```twig
{{ render_esi(controller('App\\Controller\\MenuController::menu', {
    code: 'main'
})) }}
```

### Controller Example

```php
<?php

namespace App\Controller;

use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class MenuController extends AbstractController
{
    public function __construct(
        private Environment $twig,
        private EntityManagerInterface $em,
    ) {}

    public function menu(string $code): Response
    {
        $menu = $this->em->getRepository(MenuInterface::class)
            ->findOneByCode($code);

        if (!$menu) {
            return new Response('');
        }

        $response = new Response($this->twig->render('@SyliusHappyCMSPlugin/front/menus/main.html.twig', [
            'menu' => $menu,
        ]));

        // Cache for 1 hour
        $response->setSharedMaxAge(3600);

        return $response;
    }
}
```

## Useful Methods

### Menu Methods

```php
$menu->getCode();                    // Get menu code
$menu->getName();                    // Get menu name
$menu->getItems();                   // Get all items
$menu->getRootItem();                // Get root item
$menu->isStatus();                   // Check if active
```

### MenuItem Methods

```php
$item->getName();                    // Get current translation name
$item->getTranslation($locale);      // Get specific translation
$item->getChildren();                // Get all children
$item->getPublishedChildren();       // Get only published children
$item->getParent();                  // Get parent item
$item->hasChild();                   // Check if has children
$item->hasParent();                  // Check if has parent
$item->getParents();                 // Get all parent hierarchy
$item->getFlattenParents();          // Get parent path as string
$item->getPublishState();            // Get publish state
$item->getClassAttribute();          // Get CSS class
$item->getPosition();                // Get display order
$item->isTarget();                   // Check if opens in new window
```

### MenuItemTranslation Methods

```php
$translation->getName();             // Get display name
$translation->getUrl();              // Get link URL
$translation->getLocale();           // Get language code
```

## Best Practices

1. **Use unique codes** - Menu codes should be descriptive and unique (e.g., "header", "footer", "sidebar")
2. **Publish states** - Use publish states to control visibility without deleting items
3. **CSS classes** - Use `classAttribute` for styling instead of inline styles
4. **Performance** - Use ESI for frequently used menus on high-traffic sites
5. **Hierarchies** - Keep menu depth reasonable (3-4 levels max) for better UX
6. **Translations** - Ensure all menu items have translations for all active locales
7. **Root items** - The plugin automatically manages root items, don't create them manually

## Troubleshooting

### Menu Not Appearing

1. Check menu status is active
2. Verify menu items are published
3. Ensure template exists at correct path
4. Clear cache: `php bin/console cache:clear`

### Wrong Hierarchy

1. Check parent relationships in admin
2. Use `getParents()` to debug hierarchy
3. Verify nested set values are correct

### Translation Issues

1. Ensure translations exist for active locale
2. Check `currentLocale` is set correctly
3. Verify `getTranslation()` returns valid translation

### Template Not Found

```
TemplateNotFoundException: Template "@SyliusHappyCMSPlugin/front/menus/main.html.twig" not found.
```

**Solution**: Create the template or specify custom template path:
```twig
{{ happy_cms_menu('main', {
    template: '@App/menus/navigation.html.twig'
}) }}
```

## Demo Command

The plugin includes a demo command to create sample menus:

```bash
php bin/console happycms:starter:create-demo-menu
```

This creates:
- Main navigation menu
- Footer menu
- Example hierarchical structure

**Source**: `src/Command/Starter/CreateDemoMenuCommand.php`

## Additional Resources

For technical implementation details:
- **[Menu Entity](../src/Entity/Menu/Menu.php)** - Menu model
- **[MenuItem Entity](../src/Entity/Menu/MenuItem.php)** - Menu item model
- **[MenuExtension](../src/Twig/Menu/MenuExtension.php)** - Twig extension
- **[CreateDemoMenuCommand](../src/Command/Starter/CreateDemoMenuCommand.php)** - Example command

