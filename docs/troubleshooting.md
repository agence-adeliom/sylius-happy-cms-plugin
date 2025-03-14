## Class 'Adeliom\SyliusHappyCMSPlugin\Entity\Seo\SeoInterface' does not exist

Make sure you have included the bundle's doctrine configuration in your `config/packages/doctrine.yaml`
and that you resolve correctly the interfaces

```yaml
# config/packages/doctrine.yaml
imports:
    - { resource: "@SyliusHappyCMSPlugin/config/packages/doctrine.yaml" }

doctrine:
  orm:
    resolve_target_entities:
      Adeliom\SyliusHappyCMSPlugin\Entity\Seo\SeoInterface: Adeliom\SyliusHappyCMSPlugin\Entity\Seo\Seo
      Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface: App\Entity\HappyCMS\Media\Media
      Adeliom\SyliusHappyCMSPlugin\Entity\Media\FolderInterface: App\Entity\HappyCMS\Media\Folder
      Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlockInterface: App\Entity\HappyCMS\SharedBlock\SharedBlock
      Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlockTranslationInterface: App\Entity\HappyCMS\SharedBlock\SharedBlockTranslation
```
