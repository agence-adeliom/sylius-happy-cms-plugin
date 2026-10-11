# Upgrade

## Unreleased (2.3.x)

### New option: leave the `PageContentBlock` model out of the Doctrine mapping

Defaults to `true` (current behaviour):

```yaml
sylius_happy_cms:
    page_builder:
        map_page_content_block: true
```

If your page entity has no `contentBlocks` association (no relational page builder), set it to `false`:
`doctrine:schema:validate` no longer reports the `PageContentBlock#contentOwner` association. If you pointed a
Doctrine mapping of the plugin `Entity/Page` namespace to a directory without plugin classes, or disabled the plugin
auto mapping, to avoid that error, you can map the plugin directory again. See
[Doctrine mapping](docs/configuration.md#doctrine-mapping).

### Inheritance below an entity extending a plugin model

`Adeliom\SyliusHappyCMSPlugin\EventListener\Doctrine\MappedSuperclassAssociationDeclaringClassListener` (Doctrine
`loadClassMetadata` listener, priority -100) is registered by the plugin. It sets the declaring class of the
associations that Sylius `ORMMappedSuperClassSubscriber` removes from the plugin mapped superclasses (`routes`,
`children`, `items`…), which lets a single table or joined inheritance subclass of your entity load
(it failed with `Property "routes" … was already declared`). Only associations without a declaring class whose
property belongs to a plugin class are changed. If your project has its own listener for this, you can remove it.
