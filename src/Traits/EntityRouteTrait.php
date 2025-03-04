<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Traits;

use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Route as OrmRoute;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

trait EntityRouteTrait
{
    #[ORM\ManyToMany(targetEntity: OrmRoute::class, cascade: ['persist', 'remove'])]
    protected Collection $routes;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'channel_id', nullable: true, onDelete: 'SET NULL')]
    protected ?ChannelInterface $channel = null;

    public function __construct()
    {
        $this->routes = new ArrayCollection();
    }

    /**
     * @return Collection<int, OrmRoute>
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
            /**
             * @var OrmRoute $route
             */
            if ($preview === $route->getOption(EntityRouteIndexer::OPTION_PREVIEW)) {
                if ($route instanceof RouteObjectInterface) {
                    $route->setContent($this);

                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * @param Collection<int, OrmRoute> $routes
     */
    public function setRoutes(Collection $routes): void
    {
        $this->routes = $routes;
    }

    public function addRoute(OrmRoute $route): void
    {
        if (!$this->routes->contains($route)) {
            $this->routes->add($route);
        }
    }

    public function removeRoute(OrmRoute $route): void
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
}
