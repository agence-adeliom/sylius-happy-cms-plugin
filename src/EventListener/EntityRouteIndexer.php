<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\ContentRepository;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Route;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

#[AsDoctrineListener('postPersist')]
#[AsDoctrineListener('postUpdate')]
class EntityRouteIndexer
{
    public const ROUTE_PREVIEW = 'route_preview_';

    public const ROUTE_ONLINE = 'route_online_';

    public const OPTION_PREVIEW = 'preview_behavior';

    public function __construct(
        protected ContentRepository $contentRepository,
        protected EntityManagerInterface $entityManager,
    ) {
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $entity = $event->getObject();

        if ($entity instanceof TranslationInterface) {
            $entity = $entity->getTranslatable();
        }

        if (!$entity instanceof CmsRoutableInterface) {
            return;
        }

        if ($entity->isOnline()) {
            $this->manageRoutes($entity, self::ROUTE_ONLINE);
        }

        if ($entity->previewIsAvailable()) {
            $this->manageRoutes($entity, self::ROUTE_PREVIEW);
        }

        $event->getObjectManager()->persist($entity);
        $event->getObjectManager()->flush();
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $entity = $event->getObject();

        if ($entity instanceof TranslationInterface) {
            $entity = $entity->getTranslatable();
        }

        if (!$entity instanceof CmsRoutableInterface) {
            return;
        }

        $oldRoutes = $this->getOldRoutes($entity);

        if ($entity->isOnline()) {
            $this->manageRoutes($entity, self::ROUTE_ONLINE);
        }

        if ($entity->previewIsAvailable()) {
            $this->manageRoutes($entity, self::ROUTE_PREVIEW);
        }

        $event->getObjectManager()->persist($entity);
        $event->getObjectManager()->flush();

        if ([] !== $oldRoutes) {
            $this->updateChildRoutes($entity, $oldRoutes);
        }
    }

    private function manageRoutes(CmsRoutableInterface &$entity, string $routeNamePrefix = ''): void
    {
        foreach ($entity->getTranslations() as $translation) {
            $routeName = $routeNamePrefix .
                $translation->getLocale() . '_' .
                $entity->getRouteUnikName()
            ;

            // Route exists ?
            $route = $entity->getRoutes()->filter(
                static fn (Route $route) => $route->getName() === $routeName,
            )->first();

            if (!($route instanceof Route)) {
                $route = new Route();
                $route->setName($routeName);
            }

            $route->setMethods($entity->getRouteMethods());
            $route->setRequirements($entity->getRouteRequirements($translation));
            $route->setDefaults($entity->getRouteDefaults($translation));
            $route->setSchemes($entity->getRouteSchemes($translation));
            $route->setHost($entity->getRouteHost($translation));
            $route->setStaticPrefix(
                $entity->getRouteStaticPrefix($translation, $routeNamePrefix === self::ROUTE_PREVIEW),
            );
            $route->setVariablePattern(
                $entity->getVariablePattern($translation, $routeNamePrefix === self::ROUTE_PREVIEW),
            );
            $route->setOption(self::OPTION_PREVIEW, $routeNamePrefix === self::ROUTE_PREVIEW);
            $route->setDefault(RouteObjectInterface::CONTENT_ID, $this->contentRepository->getContentId($entity));
            $entity->addRoute($route);
        }
    }

    private function getOldRoutes(CmsRoutableInterface|\Sylius\Resource\Model\TranslatableInterface $entity): array
    {
        $oldRoutes = [];
        foreach ($entity->getTranslations() as $translation) {
            foreach ([self::ROUTE_ONLINE, self::ROUTE_PREVIEW] as $routeNamePrefix) {
                $prefix = $routeNamePrefix . $translation->getLocale();
                $routeName = $prefix . '_' . $entity->getRouteUnikName();

                // Route exists ?
                $route = $entity->getRoutes()->filter(static fn (Route $route) => $route->getName() === $routeName)->first();
                if ($route) {
                    $oldRoutes[$prefix] = [
                        'from' => $route->getStaticPrefix()
                    ];
                }
            }
        }

        return $oldRoutes;
    }

    private function updateChildRoutes(CmsRoutableInterface|\Sylius\Resource\Model\TranslatableInterface $entity, array $oldRoutes): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        foreach ($entity->getTranslations() as $translation) {
            foreach ([self::ROUTE_ONLINE, self::ROUTE_PREVIEW] as $routeNamePrefix) {
                $prefix = $routeNamePrefix . $translation->getLocale();
                $routeName = $prefix . '_' . $entity->getRouteUnikName();

                // Route exists ?
                $route = $entity->getRoutes()->filter(static fn (Route $route) => $route->getName() === $routeName)->first();
                if ($route) {
                    $oldRoutes[$prefix]['to'] = $route->getStaticPrefix();
                    $isFromPreview = str_ends_with($oldRoutes[$prefix]['from'], '-preview') && $routeNamePrefix === self::ROUTE_PREVIEW;
                    $isToPreview = str_ends_with($oldRoutes[$prefix]['to'], '-preview') && $routeNamePrefix === self::ROUTE_PREVIEW;
                    $connection->executeQuery("UPDATE `orm_routes` SET staticPrefix = REPLACE(staticPrefix, ?, ?) WHERE staticPrefix LIKE ? AND name LIKE ?", [
                        str_replace($isFromPreview ? '-preview' : '', '', $oldRoutes[$prefix]['from']),
                        str_replace($isToPreview ? '-preview' : '', '', $oldRoutes[$prefix]['to']),
                        str_replace($isFromPreview ? '-preview' : '', '', $oldRoutes[$prefix]['from'] . '%'),
                        $prefix . '%',
                    ]);
                }
            }
        }
        $connection->commit();
    }
}
