# Channels

Menus, shared blocks and configurations can be attached to one or several Sylius channels.

This is the **data foundation** for multi-site projects: the plugin stores the relation, lets administrators edit it and lets
you read per-channel configuration values. Filtering lists by channel, permissions per channel and front-end filtering of
menus or shared blocks are **not** done by the plugin, they stay in your project.

> `Page` and your routable entities are different: a page always belongs to **one** channel (it carries a route with a host).
> `Media` and `Folder` are not attached to channels.

## Empty means "all channels"

| Channels selected | Meaning |
| :-- | :-- |
| none | Available on **all** channels (same behavior as before the feature) |
| one or several | Available on these channels |

Updating the plugin never hides or changes existing content: every existing menu, shared block and configuration has an empty
relation, so it stays available everywhere.

Keys stay unique **globally** (`Menu.code`, `SharedBlock.key`, `Config.key`): one entity can cover several channels without a duplicate key.

## Admin

The menu, shared block and configuration forms get a **Channels** multiple select with the help text
"Leave empty to make this content available on all channels" (`sylius_happy_cms.admin.channels.help`).

## Per-channel configuration values

A configuration keeps one **default value** per locale. You can optionally define a different value for a channel:

- In the configuration form, each locale shows the default value field plus one field per **eligible channel**, with the same
  field type as the default value (text, JSON, WYSIWYG, image, boolean, date, etc.).
- Eligible channels are the channels attached to the configuration, or **all** Sylius channels when it has none.
- An empty channel field means "no override": the default value is used.
- A **boolean** configuration gets a three-state select per channel (inherit the default, yes, no), so a channel can switch a default `true` off. Overrides are stored as `"1"` and `"0"`.
- Eligible channels come from the saved configuration: after attaching a new channel, save once to see its field.

Overrides are stored as raw strings in the `channel_values` JSON column of the configuration translation
(`{"FR_WEB": "value"}`), exactly like the default `value` (a JSON or HTML value stays a string, never decoded or re-encoded).

Limits to know:

- The channel is referenced by its **code**, without a foreign key. Deleting or renaming a channel leaves orphan overrides,
  they are kept on save and still apply if the channel code is asked for.
- You cannot query overrides by channel in SQL.
- The form shows one field per eligible channel and per locale, with no cap. Review the UX if a project goes beyond about ten channels.

## Reading a configuration value

Twig:

```twig
{# current channel, current locale #}
{{ happy_cms_config('site_title') }}

{# a given channel, by code #}
{{ happy_cms_config('site_title', 'FR_WEB') }}
```

PHP:

```php
use Adeliom\SyliusHappyCMSPlugin\Services\Config\ConfigValueResolver;

public function __construct(private ConfigValueResolver $configValueResolver) {}

$title = $this->configValueResolver->resolve('site_title');                // current channel and locale
$title = $this->configValueResolver->resolve('site_title', 'FR_WEB');      // channel code
$title = $this->configValueResolver->resolve('site_title', $channel, 'fr_FR'); // ChannelInterface and locale
```

Resolution:

1. The override of the channel for the locale (current channel when none is given).
2. Otherwise the default value of the same locale.
3. The locale falls back to the Sylius default locale when there is no translation. Resolving never creates a translation.

Rules:

- Unknown key returns `null`. Unknown channel code, or no current channel (CLI), returns the default value.
- The `channels` relation of the configuration is **ignored**: the override of the requested channel applies even if the channel is not (or no longer) attached.
- The value is converted by type like the `ConfigTranslation` getters: `boolean` gives a `bool`, `date`, `time` and `datetime` give a `\DateTime` (or `null` if invalid), other types return the raw string. For `image`, you get the raw stored value, the `Media` entity is not hydrated.

## Attach your own entities to channels

The plugin ships `ChannelAwareInterface` and `ChannelAwareTrait` (`Adeliom\SyliusHappyCMSPlugin\Entity\Channel`).
The trait holds the logic, **not the ORM mapping**: declare the property in your class with an **explicit join table name**,
so it does not depend on your concrete class name.

```php
#[ORM\ManyToMany(targetEntity: ChannelInterface::class)]
#[ORM\JoinTable(
    name: 'app_brand_channels',
    joinColumns: [new ORM\JoinColumn(name: 'brand_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
    inverseJoinColumns: [new ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
)]
protected Collection $channels;

public function __construct()
{
    $this->initializeChannels();
}
```

Then generate your own migration with `doctrine:migrations:diff`.
