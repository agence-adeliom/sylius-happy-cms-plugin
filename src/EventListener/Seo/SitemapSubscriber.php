<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener\Seo;

use Adeliom\SyliusHappyCMSPlugin\Event\Seo\AfterSitemapEntities;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsSeoInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Seo\Sitemap\SitemapDumperInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\GoogleMultilangUrlDecorator;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Psr\EventDispatcher\EventDispatcherInterface;
use Sylius\Resource\Model\AbstractTranslation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapSubscriber implements EventSubscriberInterface
{
    /**
     * @param iterable<SitemapDumperInterface> $sitemapDumpables
     */
    public function __construct(
        private bool $sitemap,
        private iterable $sitemapDumpables,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SitemapPopulateEvent::class => 'populate',
        ];
    }

    public function populate(SitemapPopulateEvent $event): void
    {
        if ($this->sitemap) {
            $urls = $event->getUrlContainer();
            $urlGenerator = $event->getUrlGenerator();
            foreach ($this->sitemapDumpables as $sitemapDumpable) {
                /**
                 * @var AfterSitemapEntities $event
                 */
                $event = $this->eventDispatcher->dispatch(new AfterSitemapEntities($sitemapDumpable->getEntities()));
                $entities = $event->getEntities();
                $replaceUrl = \Closure::fromCallable([$sitemapDumpable, 'replaceUrl']);
                if ($entities) {
                    foreach ($entities as $entity) {
                        /** @var CmsSeoInterface&AbstractTranslation $canonicalTranslation */
                        $canonicalTranslation = $entity->getTranslation();
                        if ($canonicalTranslation->getSEO()->getSitemap()) {
                            $url = $this->getUrl($urlGenerator, $sitemapDumpable, $entity, $canonicalTranslation, $replaceUrl);
                            $concreteUrl = new UrlConcrete($url, $sitemapDumpable->getLastModifiedDate($entity));
                            $decoratedUrl = new GoogleMultilangUrlDecorator($concreteUrl);

                            foreach ($entity->getTranslations() as $translation) {
                                /** @var CmsSeoInterface&AbstractTranslation $translation */
                                if ($canonicalTranslation !== $translation && $translation->getSEO()->getSitemap()) {
                                    $url = $this->getUrl($urlGenerator, $sitemapDumpable, $entity, $translation, $replaceUrl);
                                    $decoratedUrl->addLink($url, $translation->getLocale());
                                }
                            }
                            $urls->addUrl($decoratedUrl, $sitemapDumpable->getSitemapSection());
                        }
                    }
                }
            }
        }
    }

    private function getUrl(
        UrlGeneratorInterface $urlGenerator,
        SitemapDumperInterface $sitemapDumpable,
        CmsRoutableInterface $entity,
        CmsSeoInterface&AbstractTranslation $translation,
        ?callable $replaceUrl = null,
        ?int $page = null,
    ): string {
        $params = $sitemapDumpable->getSitemapRouteParams($entity);
        $params['_locale'] = $translation->getLocale();

        $url = $urlGenerator->generate(
            $sitemapDumpable->getSitemapRoute(),
            $params,
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        if ($updatedUrl = $replaceUrl($url, $entity)) {
            $url = $updatedUrl;
        }

        return $url;
    }
}
