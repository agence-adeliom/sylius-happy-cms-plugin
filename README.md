<div align="center">

# Sylius Happy CMS Plugin


</div>


![Happy CMS banner](docs/screens/happy_cms.jpg "Happy CMS banner")

<div align="center">

[Overview](#overview) • [Installation](#installation) • [Documentation](#documentation)

</div>

---

## Overview

Happy CMS is a Content Management System (CMS) plugin for Sylius that enables you to create and manage dynamic, routable Sylius resources content with ease. It provides a flexible framework for building pages, managing blocks of content, and defining custom routable entities, all integrated seamlessly into your Sylius e-commerce platform.

### A duo: Happy CMS + Easy CRUD

This plugin is built to work hand in hand with [Sylius Easy CRUD Plugin]() to provide a seamless experience for managing CMS routable resources in front, and CRUD admin interfaces easily within Sylius.

---

## Installation

### 1. Install via Composer

```bash
composer require agence-adeliom/sylius-happy-cms-plugin
composer require --dev symfony/maker-bundle
```

### 2. Enable the Bundle

Add the plugin to `config/bundles.php`:

```php
<?php

return [
    // ...
    Adeliom\SyliusEasyCrudPlugin\SyliusEasyCrudPlugin::class => ['all' => true],
    Adeliom\SyliusHappyCMSPlugin\SyliusHappyCMSPlugin::class => ['all' => true],
   
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true, 'test' => true],
];
```

### 3. Import Configuration

In `config/packages/_sylius.yaml`:

```yaml
imports:
  - { resource: "@SyliusEasyCrudPlugin/config/config.yaml" }
  - { resource: "@SyliusHappyCMSPlugin/config/config.yaml" }
```

### 4. Import Routes

In `config/routes.yaml`:

```yaml
sylius_happy_cms:
  resource: "@SyliusHappyCMSPlugin/config/routes.yaml"
  
sylius_easy_crud:
  resource: "@SyliusEasyCrudPlugin/config/routes.yaml"
```

### 4. Generate default files in your project (entities, repositories and admin classes) :

Actually, we don't have Symfony recipes, so we created a command to generate files automatically.

```bash
php bin/console make:happy-cms:install
```
This command will :
- Create all entities, repositories and admin class
- update config/routes.yaml by adding route properly declared
- update config/packages/sylius_resource.yaml by adding sylius routes properly declared
- update config/packages/sylius_happy_cms.yaml by adding new files properly declared

If something goes wrong, you can do those actions manually, check [detailed configuration](./docs/DETAILED_CONFIG.md).

### 5. Install Assets

```bash
php bin/console assets:install
```

### 6. Update database

```bash
php bin/console doc:mig:diff
php bin/console doc:mig:mig
php bin/console cache:clear
```

At this point, the plugin should be installed and ready to use!

---

## Documentation

- **[Configure Homepage](./docs/HOMEPAGE.md)**
- **[Create custom routable entities](./docs/CREATE_ROUTABLE_ENTITIES.md)**
- **[Create custom CMS blocks](./docs/CREATE_BLOCK.md)**
- **[Detailed default configuration](./docs/DETAILED_CONFIG.md)**
- **[How routing work](./docs/ROUTING.md)**

---

<div align="center">

**If this plugin helped you, please consider giving it a ⭐ on GitHub!**

Made with ❤️ by [Adeliom](https://www.adeliom.com/)


[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://php.net)
[![Sylius Version](https://img.shields.io/badge/sylius-%5E2.0-blue)](https://sylius.com)
[![Latest Version](https://img.shields.io/packagist/v/agence-adeliom/sylius-easy-crud-plugin)](https://packagist.org/packages/agence-adeliom/sylius-happy-cms-plugin)

</div>
