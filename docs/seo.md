# SEO features for a resource

1. [Entities and admin configuration](#entities-and-admin-configuration)
2. [Frontend twig helpers](#frontend-twig-helpers)
3. [Sitemap.xml](#sitemapxml)
4. [Robots.txt](#robotstxt)
4. [llms.txt](#llmstxt)

## Entities and admin configuration

### 1. Configure entities

- All generated entities with SEO enabled will have the `EntitySeoTrait` trait, which adds the following properties:
    - `seoTitle`
    - `seoDescription`
    - `seoKeywords`
    - `seoCanonicalUrl`
    - `seoRobots`

- Add the trait to the entity translation if the resource is translatable, or to the entity itself if not translatable.

```php
# src/Entity/PostTranslation.php
class PostTranslation implements TranslationInterface, CmsSeoInterface
{
    use EntitySeoTrait {
        EntitySeoTrait::__construct as private SEOConstruct;
    }
}
```

- The `getSeoTranslations()` method must be implemented in the entity, and should return a collection of translations implementing `CmsSeoInterface`.
- This is used to build the admin form.
```php
# src/Entity/Post.php
class Post
{
    /**
     * @return Collection<int, CmsSeoInterface>
     */
    public function getSeoTranslations(): Collection
    {
        return $this->translations;
    }
}
```

### 2. Configure admin form

```php
# src/Admin/PostAdmin.php
class PostAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        yield TabField::new('seo', 'sylius_happy_cms.page.admin.tab.seo');

        // seoTranslations is the method defined in the entity (symfony call getSeoTranslations automatically)
        yield TranslationField::new('seoTranslations', 'sylius_happy_cms.page.admin.field.seo.translations')
            ->addField(
                SEOField::new('seo', 'sylius_happy_cms.page.admin.field.seo')
                    ->setDisabled(false)
                    ->setRequired(true)
            )
            ->hideOnIndex();
    }
}
```

## Frontend twig helpers

From the seo property of the entity, you can render the seo metas, title and breadcrumb with the following twig functions:
- seo_metas
- seo_title
- seo_breadcrumb

```html
{{- seo_metas(seo) -}}

# generates:
<meta name="description" content="...">
<meta name="keywords" content="...">
...
Look full support here : templates/front/seo/block-metas.html.twig
```

```html
{{- seo_title(seo) -}}
# generates:
<title>...</title>
```

```html
{{- seo_breadcrumb(seo) -}}
# generates:
<nav id="happy_cms_seo-breadcrumbs" aria-label="breadcrumb">
    <ol itemscope itemtype="http://schema.org/BreadcrumbList"
        <li itemprop="itemListElement" class="breadcrumb-item"><a href="/">Home</a></li>
# Look here : templates/front/seo/block-breadcrumb.html.twig
```

## Sitemap.xml

To add a sitemap index listing a resource's datas create a class extending `Adeliom\SyliusHappyCMSPlugin\Services\Seo\Sitemap`, or implementing `Adeliom\SyliusHappyCMSPlugin\Services\Seo\Sitemap\SitemapDumperInterface`

### Example

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

## Robots.txt

You can just create a `robots.txt` file in your `./public` folder with the following content to allow search engines to index your pages:

```User-agent: *
Allow: /
Sitemap: sitemap-post.xml
Sitemap: sitemap-page.xml
...
```

Or you can create a route and controller to generate it dynamically, for example:

```php
<?php
// src/Controller/RobotsController.php  
class RobotsController extends AbstractController
{
    #[Route('/robots.txt', name: 'app_robots')]
    public function index(): Response
    {
        // Manual content
        $content = "User-agent: *\nAllow: /\nSitemap: /sitemap-posts.xml\nSitemap: /sitemap-pages.xml\n";

        // Or get content from configuration
        // ConfigurationRepositoryInterface is a repository for the Configuration entity,
        // which is a resource provided by Happy CMS to store key-value pairs in the database.
        $content = $this->configurationRepository
            ->findOneBy(['key' => 'robots_txt'])
            ->getValue();   

        return new Response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
```

And you can administrate the content of the robots.txt file with the Happy CMS resource called "Configuration", by creating a configuration with the key "robots_txt" and the content of the robots.txt file in the value field, then you can update the controller to get the content from the configuration entity instead of hardcoding it.:

![Robot admin configuration](docs/screens/robots.png "Robot admin configuration")

## llms.txt

Use the same approach as the robots.txt to create a llms.txt file, which is used to provide information about the language and locale of the pages to search engines. The content of the llms.txt file should be in the following format:

```md
# llms.txt
...    
``` 
