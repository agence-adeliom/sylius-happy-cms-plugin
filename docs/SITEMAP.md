# Add a new sitemap index

To add a sitemap index listing a resource's datas create a class extending `Adeliom\SyliusHappyCMSPlugin\Services\Seo\Sitemap`, or implementing `Adeliom\SyliusHappyCMSPlugin\Services\Seo\Sitemap\SitemapDumperInterface`

## Example

Example with a sitemap listing of posts:

```php
<?php
// src/Service/PostSitemapDumper.php

declare(strict_types=1);

namespace App\Service;

use App\Repository\PostRepository;

class PostSitemapDumper extends AbstractSitemapDumper
{
    public function __construct(
        private PostRepository $postRepository,
    ) {
    }

    public static function getSitemapSection(): string
    {
        return 'posts';
    }

    public function getEntities(): array
    {
        return $this->postRepository->findAll();
    }
}
```
Now when using the following command : 
```bash
bin/console presta:sitemaps:dump 
```

This will generate a sitemap like this:

```xml
<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd">
    <sitemap>
        <loc>http://localhost/sitemap.posts.xml</loc>
        <lastmod>2024-01-11T00:00:00+00:00</lastmod>
    </sitemap>
</sitemapindex>
```

with the post sitemap looking like this :
```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <url>
        <loc>http://localhost/en_US/my-post</loc>
        <lastmod>2024-01-11T00:00:00+00:00</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.5</priority>
        <xhtml:link rel="alternate" hreflang="fr" href="http://localhost/fr_FR/mon-article"/>
    </url>
</urlset>
```
