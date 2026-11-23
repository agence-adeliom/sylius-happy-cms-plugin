<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures;

use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityPublishableTrait;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Traits\EntityRouteTrait;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\TranslatableTrait;
use Sylius\Resource\Model\TranslationInterface;

/**
 * Same trait order as an entity made by make:happy-cms:generate-cms-model: the translations come first.
 */
class MakerShapedEntity implements ResourceInterface, TranslatableInterface, CmsRoutableInterface
{
    use TranslatableTrait {
        TranslatableTrait::__construct as private _initializeTranslationsCollection;
        getTranslation as private _doGetTranslation;
    }
    use EntityPublishableTrait {
        EntityPublishableTrait::__construct as private _publishableConstruct;
    }
    use EntityRouteTrait {
        EntityRouteTrait::__construct as private _entityRouteConstruct;
    }
    use EntityIdTrait;

    public function __construct()
    {
        $this->_initializeTranslationsCollection();
        $this->_publishableConstruct();
        $this->_entityRouteConstruct();
    }

    public function getTranslation(?string $locale = null): TranslationInterface
    {
        return $this->_doGetTranslation($locale);
    }

    public function getRouteUnikName(): string
    {
        return 'happy_cms_article_' . $this->getId();
    }

    public function getRouteStaticPrefix(TranslationInterface $translation): string
    {
        $slug = $translation instanceof MakerShapedEntityTranslation ? $translation->getSlug() : '';

        return sprintf('/%s/article/%s', $translation->getLocale(), $slug);
    }

    protected function createTranslation(): TranslationInterface
    {
        return new MakerShapedEntityTranslation();
    }
}
