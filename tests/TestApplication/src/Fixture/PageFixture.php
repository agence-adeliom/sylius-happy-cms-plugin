<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Fixture;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class PageFixture extends AbstractFixture
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $pageClass,
    ) {
    }

    public function load(array $options): void
    {
        foreach ($options['custom'] as $pageData) {
            $this->createPage($pageData);
        }

        $this->entityManager->flush();
    }

    public function getName(): string
    {
        return 'happy_cms_page';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
        $optionsNode
            ->children()
                ->arrayNode('custom')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->isRequired()->end()
                            ->scalarNode('slug')->isRequired()->end()
                            ->scalarNode('template')->defaultValue('default')->end()
                            ->scalarNode('publish_state')->defaultValue(ThreeStateStatusEnum::PUBLISHED)->end()
                            ->arrayNode('translations')
                                ->useAttributeAsKey('locale')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('locale')->isRequired()->end()
                                        ->scalarNode('name')->isRequired()->end()
                                        ->scalarNode('slug')->isRequired()->end()
                                        ->scalarNode('metaTitle')->defaultNull()->end()
                                        ->scalarNode('metaDescription')->defaultNull()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function createPage(array $pageData): void
    {
        /** @var PageInterface $page */
        $page = new $this->pageClass();

        $page->setSlug($pageData['slug']);
        $page->setTemplate($pageData['template']);
        $page->setPublishState($pageData['publish_state']);

        // Set translations
        foreach ($pageData['translations'] as $locale => $translationData) {
            $page->setCurrentLocale($locale);
            $page->setFallbackLocale($locale);

            $translation = $page->getTranslation($locale);
            $translation->setName($translationData['name']);
            $translation->setSlug($translationData['slug']);

            if (isset($translationData['metaTitle'])) {
                $translation->setMetaTitle($translationData['metaTitle']);
            }

            if (isset($translationData['metaDescription'])) {
                $translation->setMetaDescription($translationData['metaDescription']);
            }
        }

        $this->entityManager->persist($page);
    }
}
