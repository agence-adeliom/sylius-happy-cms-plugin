# Demo Content

You can test the plugin with some demo content to explore its features and capabilities. The demo content includes sample pages, menus, and blocks that showcase the functionalities of the Happy CMS plugin.

Execute the following commands to create demo pages and a demo menu:

```bash
bin/console happycms:starter:create-demo-pages
bin/console happycms:starter:create-demo-menu
```

You can take a look at those commands files to see how you can create content programmatically using the plugin's services and entities:

```php
#[AsCommand(
    name: 'happycms:starter:create-demo-pages',
    description: 'Create homepage',
)]
class CreateDemoPagesCommand extends AbstractContentCommand
{
    ...
}
```

Those generate a homepage with all default blocks in place.
![Happy CMS banner](screens/sylius_happy_cms_homepage.png "Happy CMS homepage")

