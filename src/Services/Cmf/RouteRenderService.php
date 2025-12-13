<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\Route as OrmRoute;
use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Event\Route\RouteRenderServiceEvent;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Security\ContentDocumentVoter;
use Adeliom\SyliusHappyCMSPlugin\Services\Seo\BreadcrumbCollection;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactory;
use Sylius\Resource\Metadata\Metadata;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

class RouteRenderService extends AbstractController
{
    public function __construct(
        protected RouterInterface $router,
        protected RequestConfigurationFactory $requestConfigurationFactory,
        protected Environment $twig,
        protected EventDispatcherInterface $eventDispatcher,
        protected BreadcrumbCollection $breadcrumb,
        protected KernelInterface $kernel,
        protected EntityManagerInterface $manager,
        protected ParameterBag $parameterBag,
    ) {
    }

    public function invalidCache(): bool
    {
        /** @var string|class-string<RouteInterface>|null $routeClass */
        $routeClass = $this->parameterBag->get('cmf_routing.dynamic.persistence.orm.route_class');
        if (is_string($routeClass) && class_exists($routeClass)) {
            $qb = $this->manager->getRepository($routeClass)->createQueryBuilder('r');
            $routes = $qb
                ->select()
                ->where($qb->expr()->isNotNull('r.lastModification'))
                ->getQuery()
                ->getResult();
            $this->manager->beginTransaction();
            if (is_array($routes)) {
                foreach ($routes as $route) {
                    /** @var RouteInterface $route */
                    $route->setLastModification(new \DateTime());
                    $this->manager->persist($route);
                }
            }
            $this->manager->flush();
            $this->manager->commit();
        }

        return true;
    }

    public function renderAction(
        CmsRoutableInterface $contentDocument,
        Request $request,
        ?RouteInterface $route = null,
    ): Response {
        if (null === $route) {
            /**
             * @var ?OrmRoute $route
             */
            $route = $request->attributes->get('routeDocument');
        }

        /** @var bool $preview */
        $preview = $request->get('preview') && $request->get('preview') === '1';

        if ($preview) {
            try {
                $this->denyAccessUnlessGranted(ContentDocumentVoter::PAGE_BUILDER, $contentDocument);
            } catch (AccessDeniedException $e) {
                throw new \Exception('Access Denied to preview content document');
            }
        }

        if (!$contentDocument->isOnline() && !$preview) {
            throw $this->createNotFoundException('Document is not published');
        }

        if (null === $route) {
            throw new \Exception('missing route with entity');
        }

        $cacheEnabled = $contentDocument->isHttpCacheEnabled($this->kernel->getEnvironment(), $route);

        $response = $contentDocument->renderResponse($request, new Response(null), $route, $cacheEnabled);

        if ($response->isNotModified($request)) {
            // return the 304 Response
            return $response;
        }

        [$metadata, $configuration] = $this->contentDocumentAsResource(
            get_class($contentDocument),
            $request,
        );

        if (null === $metadata || null === $configuration) {
            throw new \RuntimeException('Cannot find resource metadata or configuration for content document');
        }

        $template = $contentDocument->getRouteTemplate();
        if (null === $template) {
            $template = '@SyliusHappyCMSPlugin/front/document/default.html.twig';
        }

        $controller = $contentDocument->getRouteController();
        if (null !== $controller) {
            try {
                return $this->forward($controller, [
                    'contentDocument' => $contentDocument,
                    'request' => $request,
                ]);
            } catch (\RuntimeException $runtimeException) {
                throw $this->createAccessDeniedException($controller . ' not exists');
            }
        }

        $breadcrumbItems = $contentDocument->getBreadcrumbItems();
        foreach ($breadcrumbItems as $breadcrumbItem) {
            $this->breadcrumb->addSimpleItem(
                $breadcrumbItem['label'],
                $breadcrumbItem['route']?->getPath(),
            );
        }

        $this->twig->addGlobal('resource', $contentDocument);

        $renderEvent = new RouteRenderServiceEvent([
             'metadata' => $metadata,
             'configuration' => $configuration,
             'resource' => $contentDocument,
             'route' => $route,
             'preview' => $preview,
        ]);

        $event = $this->eventDispatcher->dispatch($renderEvent);

        return $this->render($template, $event->getParameters(), $response);
    }

    /**
     * @return array{
     *     0: ?Metadata,
     *     1: ?RequestConfiguration
     * }
     */
    private function contentDocumentAsResource(
        string $model,
        Request $request,
    ): array {
        try {
            /** @var array<string, array{
             *  classes: array{
             *     model: class-string,
             *     controller: class-string,
             *     repository: class-string,
             *     form: class-string,
             *     factory: class-string,
             *  }
             * }|null> $resources */
            $resources = $this->parameterBag->get('sylius.resources');
        } catch (InvalidArgumentException $exception) {
            return [null, null];
        }
        $metadata = $configuration = null;
        foreach ($resources as $alias => $parameters) {
            /** @var array{
             *      classes: array{
             *          model: class-string,
             *          controller: class-string,
             *          repository: class-string,
             *          form: class-string,
             *          factory: class-string,
             *      }
             * } $parameters */
            if ($parameters['classes']['model'] === $model) {
                $metadata = Metadata::fromAliasAndConfiguration($alias, $parameters);
                $configuration = $this->requestConfigurationFactory
                    ->create(
                        $metadata,
                        $request,
                    );
            }
        }

        return [$metadata, $configuration];
    }
}
