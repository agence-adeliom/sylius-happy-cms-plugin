<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Traits;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\PropertyAccess\PropertyAccessor;

trait EntityRouteTrait
{
    #[ORM\ManyToMany(targetEntity: RouteInterface::class, cascade: ['persist', 'remove'])]
    protected Collection $routes;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'channel_id', nullable: true, onDelete: 'SET NULL')]
    protected ?ChannelInterface $channel = null;

    public function __construct()
    {
        $this->routes = new ArrayCollection();
    }

    /**
     * @return Collection<int, RouteInterface>
     */
    public function getRoutes(): Collection
    {
        return $this->routes;
    }

    public function getOnlineRoute(): ?RouteObjectInterface
    {
        return $this->getRoute(false);
    }

    public function getPreviewRoute(): ?RouteObjectInterface
    {
        return $this->getRoute(true);
    }

    private function getRoute(bool $preview = false): ?RouteObjectInterface
    {
        foreach ($this->routes as $route) {
            if ($preview === $route->getOption(EntityRouteIndexer::OPTION_PREVIEW)) {
                $route->setContent($this);

                return $route;
            }
        }

        return null;
    }

    /**
     * @param Collection<int, RouteInterface> $routes
     */
    public function setRoutes(Collection $routes): void
    {
        $this->routes = $routes;
    }

    public function addRoute(RouteInterface $route): void
    {
        if (!$this->routes->contains($route)) {
            $this->routes->add($route);
        }
    }

    public function removeRoute(RouteInterface $route): void
    {
        if ($this->routes->contains($route)) {
            $this->routes->removeElement($route);
        }
    }

    /**
     * @return string[]
     */
    public function getRouteMethods(): array
    {
        return ['GET', 'POST'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRouteOptions(TranslationInterface $translation): array
    {
        return [
            'add_locale_pattern' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRouteRequirements(TranslationInterface $translation): array
    {
        return [
            '_locale' => $translation->getLocale(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRouteDefaults(TranslationInterface $translation): array
    {
        return [
            '_locale' => $translation->getLocale(),
        ];
    }

    /**
     * @return string[]
     */
    public function getRouteSchemes(TranslationInterface $translation): array
    {
        return ['https', 'http'];
    }

    public function getRouteHost(TranslationInterface $translation): ?string
    {
        if (null !== $this->getChannel()) {
            return $this->getChannel()->getHostname();
        }

        return null;
    }

    public function getRouteUnikName(): string
    {
        return \sprintf(
            '%s_%s',
            str_replace('\\', '_', self::class),
            $this->getId(),
        );
    }

    public function isHttpCacheEnabled(string $env, RouteInterface $route): bool
    {
        // Default behavior is to enable http cache
        //return $env === 'prod' ? true : false;
        return false;
    }

    // Override response header when a document controller is rendered
    // If this behavior is not wanted, you can override this method in your routable entity
    // To make this configuration working, use this framework configuration
    //     framework:
    //        http_cache:
    //            enabled: true
    //            default_ttl: 0
    // To unvalide all route cache, you can use the command : happycms:cache:invalidate
    public function renderResponse(Request $request, Response $response, RouteInterface $route, bool $cacheEnabled): Response
    {
        // If cache is disabled, we return the response as is
        if (!$cacheEnabled) {
            return $response;
        }

        // No cache in preview mode
        if ($route->getOption('preview_behavior') === true) {
            return $response;
        }

        // This timestamp is update on every persist of the entity
        // It's store into the route option 'last_modification_timestamp'
        // This allow to simply check the last modification date of the entity and before rendering all page
        // This code is executed on the controller top actions
        if ($route->getOption('last_modification_timestamp') && is_int($route->getOption('last_modification_timestamp'))) {
            // Force public cache even if a session is started
            // Carreful to not have client component in you cache
            // Or wrap those component into a sub request (esi render, or live component)
            $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
            // Set the last modification date of the entity
            $response->setLastModified((new \DateTime())->setTimestamp($route->getOption('last_modification_timestamp')));
            // No ttl to avoid cache expire mode
            // And force validation cache mode
            $response->setTtl(0);
            // Tell the client to revalidate the cache
            $response->setCache([
                                    'must_revalidate' => true,
                                ]);
            // Put cache public
            $response->setPublic();
        }

        return $response;
    }

    public function getRouteStaticPrefix(TranslationInterface $translation, bool $isPreview): string
    {
        $entity = $translation->getTranslatable();
        $parentSlug = '';
        $accessor = new PropertyAccessor();
        // TODO: rename isHomepage by isRootNode
        // Nous n'avons pas besoin de slug pour le root node
        // TODO: while loop until parent is null
        $isHomepage = false;
        if ($accessor->isReadable($entity, 'isHomePage')) {
            $isHomepage = $accessor->getValue($entity, 'isHomePage');
        }
        if (!$isHomepage) {
            if ($accessor->isReadable($entity, 'parent')) {
                if ($parent = $accessor->getValue($entity, 'parent')) {
                    $isParentHomepage = false;
                    if ($accessor->isReadable($parent, 'isHomePage')) {
                        $isParentHomepage = $accessor->getValue($parent, 'isHomePage');
                    }
                    if (!$isParentHomepage) {
                        if ($accessor->isReadable($parent, 'translation')) {
                            // access parent->translation->slug if parent is Translatable
                            $parentTranslation = $parent->getTranslation($translation->getLocale());
                            $parentSlug = $accessor->getValue($parentTranslation, 'slug');
                        } elseif ($accessor->isReadable($parent, 'slug')) {
                            // access parent->slug if parent is not Translatable
                            $parentSlug = $accessor->getValue($parent, 'slug');
                        }
                    }
                }
            }
        }

        $url = '/' . $translation->getLocale();
        if ($parentSlug ?? false) {
            $url .= '/' . $parentSlug;
        }
        if (!$isHomepage && method_exists($translation, 'getSlug')) {
            $url .= '/' . $translation->getSlug();
        }
        if ($isPreview) {
            $url .= '-preview';
        }

        return $url;
    }

    public function getVariablePattern(TranslationInterface $translation, bool $isPreview): string
    {
        return '';
    }

    public function getRouteController(): ?string
    {
        // To forward route response to a custom controller
        // return 'App\Controller\MyController::fancy';
        return null;
    }

    public function getRouteTemplate(): ?string
    {
        // render '@SyliusHappyCMSPlugin/front/document/default.html.twig' as default template
        // Feel free to change template path
        // return 'App/front/document/fancy.html.twig'
        return null;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    /**
     * @return array{label: string, route: ?RouteObjectInterface}
     */
    public function getBreadcrumbItems(): array
    {
        $list = [];
        $list[] = [
            'label' => $this->getName(),
            'route' => $this->getOnlineRoute(),
        ];

        try {
            $parent = $this->getParent();
            while ($parent !== null) {
                $list[] = [
                    'label' => $parent->getName(),
                    'route' => $parent->getOnlineRoute(),
                ];
                $parent = $parent->getParent();
            }
        } catch (\Exception $e) {
            // If getParent no exists or throws an exception, we just ignore it
        }

        /** @var array{label: string, route: ?RouteObjectInterface} $reservedList */
        $reservedList = array_reverse($list, true);

        return $reservedList;
    }

    public function getParent(): ?PageInterface
    {
        return null;
    }

    public function getName(): ?string
    {
        return $this->getTranslation()->getName();
    }
}
