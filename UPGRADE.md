# Upgrade

## Unreleased (2.3.x)

### Menus: root item key, root item creation

**Schema** — the `root_item_id` foreign key of the menu table is now `ON DELETE SET NULL` (was `CASCADE`).
With `CASCADE`, deleting a menu with its items failed under ORM 3 (`CycleDetectedException`: the menu references its
root item, which references the menu). Generate the migration of your project (`doctrine:migrations:diff`); on MySQL it
looks like:

```sql
ALTER TABLE <menu_table> DROP FOREIGN KEY <fk_root_item>;
ALTER TABLE <menu_table> ADD CONSTRAINT <fk_root_item> FOREIGN KEY (root_item_id) REFERENCES <menu_item_table> (id) ON DELETE SET NULL;
```

Deleting the root item alone no longer deletes the menu row: the menu is kept without root.

**Data** — `MenuCreationListener` created the root item without assigning it, so menus created so far have a
`NULL` `root_item_id` (the `happy_cms_menu()` Twig function finds the root by query and is not affected).
To fill it:

```sql
UPDATE <menu_table> m
SET m.root_item_id = (SELECT MIN(i.id) FROM <menu_item_table> i WHERE i.menu_id = m.id AND i.parent_id IS NULL)
WHERE m.root_item_id IS NULL;
```

**`MenuCreationListener`**:

- sets `Menu#rootItem` to the root item it creates;
- does nothing for a menu persisted with its `rootItem` already set (it used to add a second item without parent);
- takes the root item class from the Doctrine mapping of `Menu#rootItem` (`resolve_target_entities` applied) instead of
  the `sylius_happy_cms.menu_item` resource, which no longer has to exist under that name; the resource is still used
  when `prePersist()` is called without event arguments;
- takes the root item label as a new optional fourth constructor argument; a service definition overriding the
  listener with the three original arguments keeps the current label.

New options, defaults keep the current behaviour:

```yaml
sylius_happy_cms:
    menu:
        root_item:
            auto_create: true   # false: the listener is not registered (the project creates the root item)
            label: 'sylius_happy_cms.menu_item.admin.data.menu_item_root'   # translated per locale; plain text kept as is
```
