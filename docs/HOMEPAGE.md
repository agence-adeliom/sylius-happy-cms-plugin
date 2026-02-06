To override sylius home page to get the happy cms root page

```yaml
sylius_shop_homepage:
    path: /{_locale}
    methods: [GET]
    controller: Adeliom\SyliusHappyCMSPlugin\Controller\Page\PageController::homeAction
```

Then create a page with the homepage boolean set to true and it will be used as the homepage of your shop based on the current channel.

```php
$page = new Page();
$page->setHomepage(true);
...
```

For older versions of this plugin (< v2.1) :

```php
$page = new Page();
$page->setTemplate('homepage');
...

