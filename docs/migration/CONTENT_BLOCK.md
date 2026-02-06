# Migration to ContentBlock System

This document explains the migration from the JSON-based page builder system to the new ContentBlock entity system.

## Table of Contents

- [What's Changing?](#whats-changing)
- [Benefits of the New System](#benefits-of-the-new-system)
- [Data Migration](#data-migration)
- [After Migration](#after-migration)
- [FAQ](#faq)

---

## What's Changing?

### Old System (JSON)

In the old system, page content was stored in a JSON `content` field in Translation entities:

```php
// PageTranslation
protected ?array $content = [
    'block-key-1' => [
        'position' => '1',
        'block_type' => 'App\\Block\\HeroBlockType',
        'block_published' => '1',
        'title' => 'My title',
        'image' => [...],
    ],
    // ...
];
```

**Limitations**:
- ❌ Difficult to query in database
- ❌ No fine-grained version management (draft vs published)
- ❌ Cannot index or filter by block type
- ❌ No template layer management

### New System (ContentBlock Entities)

The new system uses relational `ContentBlock` entities:

```php
// ContentBlock entity
- type: string               // Block type (e.g., App\Block\HeroBlockType)
- locale: string             // Content language
- position: int              // Position in page
- published: bool            // Publication status
- layer: ?string             // Template layer (e.g., 'sidebar', 'main')
- draftData: ?array          // Draft mode data
- publishedData: ?array      // Published data
- contentOwner: PageInterface // Relation to parent page
```

**Benefits**:
- ✅ Performant SQL queries
- ✅ Separate management of draft and published versions
- ✅ Template layer support
- ✅ Ability to filter/sort by type, locale, layer
- ✅ Better data integrity

---

## Benefits of the New System

### 1. Performance and Querying

You can now easily query blocks:

```php
// Get all Hero blocks in French
$heroBlocks = $repository->findBy([
    'type' => HeroBlockType::class,
    'locale' => 'fr',
    'published' => true,
]);

// Count pages using a specific block type
$count = $repository->createQueryBuilder('cb')
    ->select('COUNT(DISTINCT cb.contentOwner)')
    ->where('cb.type = :type')
    ->setParameter('type', HeroBlockType::class)
    ->getQuery()
    ->getSingleScalarResult();
```

### 2. Draft / Published Management

Each block now has two versions:
- **draftData**: Working version visible only in back-office
- **publishedData**: Published version visible on the site

```php
$block->setDraftData(['title' => 'New title']);
$block->publish(); // Copies draftData to publishedData
$block->hasUnpublishedChanges(); // true if draftData ≠ publishedData
```

### 3. Layer Support

Blocks can be assigned to different template zones:

```php
// Assign a block to a layer
$block->setLayer('sidebar');

// Get blocks from a specific layer
$sidebarBlocks = $page->getPublishedContentBlocks('en', 'sidebar');
$mainBlocks = $page->getPublishedContentBlocks('en', 'main');
```

---

## Data Migration

### Prerequisites

1. **Backup your database** before proceeding
2. Ensure you've run Doctrine migrations to create ContentBlock tables
3. Test first with `--dry-run`

### Step 1: Test with dry-run

Before actually migrating, test the command in dry-run mode:

```bash
php bin/console happycms:migrate:content-to-blocks --dry-run
```

This command will:
- Scan all eligible entities
- Parse JSON content
- Display what would be created
- **Not persist anything to database**

**Example output**:
```
Content to ContentBlocks Migration
===================================

Scanning for CmsRoutable entities...

+------------------+-------------------------------+
| Resource         | Entity Class                  |
+------------------+-------------------------------+
| sylius_happy_cms.page | App\Entity\HappyCMS\Page\Page |
+------------------+-------------------------------+

Starting migration...

Processing resource: sylius_happy_cms.page (App\Entity\HappyCMS\Page\Page)
 42/42 [============================] 100%
  Migrated: 156 content blocks

[OK] Migration completed! 156 content blocks created, 0 errors.

This was a DRY-RUN. Run without --dry-run to persist changes.
```

### Step 2: Verification

Verify that:
- ✅ Block count matches your expectations
- ✅ No errors are displayed
- ✅ Listed resources are correct

### Step 3: Actual Migration

If everything is OK, run the migration:

```bash
php bin/console happycms:migrate:content-to-blocks
```

The command will:
- Create ContentBlock entities
- Link them to parent entities
- Persist to database

### Step 4: Post-migration Verification

After migration, verify:

```bash
# Check created ContentBlocks
SELECT COUNT(*) FROM sylius_happy_cms__page_content_block;

# Check distribution by locale
SELECT locale, COUNT(*)
FROM sylius_happy_cms__page_content_block
GROUP BY locale;

# Check published vs unpublished blocks
SELECT published, COUNT(*)
FROM sylius_happy_cms__page_content_block
GROUP BY published;
```

### Advanced Options

#### Migrate a Single Resource

If you have multiple resources and want to migrate them one by one:

```bash
php bin/console happycms:migrate:content-to-blocks --resource=sylius_happy_cms.page
```

#### Migrate with Detailed Logging

For more details during migration:

```bash
php bin/console happycms:migrate:content-to-blocks -vvv
```

---

## After Migration

### 1. Front-office Verification

- ✅ Verify all pages display correctly
- ✅ Test different languages
- ✅ Verify blocks are in correct order

### 2. Back-office Verification

- ✅ Open page editor
- ✅ Verify blocks are listed
- ✅ Test adding/removing blocks
- ✅ Test preview mode (draft)

### 3. Code Update (Optional)

If you want to take full advantage of the new system, you can:

#### Query ContentBlocks Directly

```php
// Before (JSON)
$content = $page->getTranslation()->getContent();
foreach ($content as $block) {
    // ...
}

// After (Entities)
$blocks = $page->getPublishedContentBlocks('en');
foreach ($blocks as $block) {
    $type = $block->getType();
    $data = $block->getPublishedData();
    // ...
}
```

#### Manage Draft/Published Versions

```php
// Modify a block in draft mode
$block->setDraftData(['title' => 'New title']);
$entityManager->flush();

// Publish changes
$block->publish();
$entityManager->flush();

// Check for unpublished changes
if ($block->hasUnpublishedChanges()) {
    // Display an indicator in back-office
}
```

#### Use Layers

```php
// Assign a block to a layer
$block->setLayer('sidebar');

// Get blocks from a specific layer
$sidebarBlocks = $page->getPublishedContentBlocks('en', 'sidebar');
$mainBlocks = $page->getPublishedContentBlocks('en', 'main');
```

### 4. Cleanup (Optional, After Validation)

Once you're certain everything works correctly, you might consider:

1. **Remove the old `content` field** (do this carefully)
2. **Create a Doctrine migration** to drop the column
3. **Update your forms** to no longer use the JSON field

⚠️ **Warning**: Keep the old field for at least a few weeks/months to allow rollback if necessary.

---

## FAQ

### How to manage multiple environments?

1. **Development**: Migrate on dev first
2. **Staging**: Test on a staging environment
3. **Production**:
    - Schedule a maintenance window
    - Make a complete backup
    - Run the migration
    - Verify immediately

### Are performances impacted?

**No, quite the opposite!** The new system is more performant because:
- SQL queries are optimized
- Ability to index columns (type, locale, position, published)
- No need to deserialize JSON on each query

### I want to keep the old system

You can continue using the old JSON system. The new system is **opt-in**:
- Simply don't run the migration command
- The `content` field continues to work normally

### I'm getting errors during migration

If you encounter errors:

1. **Check logs** with `-vvv`
2. **Use `--dry-run`** to identify the issue
3. **Verify JSON structure** of your `content` field
4. **Open an issue** on GitHub with:
    - Complete error message
    - Example of problematic JSON structure
    - Plugin version

### Can I migrate in stages?

Yes! Use the `--resource` option:

```bash
# Migrate pages first
php bin/console happycms:migrate:content-to-blocks --resource=sylius_happy_cms.page

# Then migrate other custom resources
php bin/console happycms:migrate:content-to-blocks --resource=app.custom_entity
```
