<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\Route as OrmRoute;
use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Event\Route\RouteRenderServiceEvent;
use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Security\ContentDocumentVoter;
use Adeliom\SyliusHappyCMSPlugin\Services\Seo\BreadcrumbCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
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
        $routeClass = $this->parameterBag->get('cmf_routing.dynamic.persistence.orm.route_class');
        if (is_string($routeClass)) {
            $qb = $this->manager->getRepository($routeClass)->createQueryBuilder('r');
            $routes = $qb
                ->select()
                ->where($qb->expr()->like('r.options', ':option'))
                ->setParameter('option', '%' . EntityRouteIndexer::OPTION_LAST_MODIFICATION_TIMESTAMP . '%')
                ->getQuery()
                ->getResult();

            foreach ($routes as $route) {
                /**
                 * @var RouteInterface $route
                 */
                $route->setOption(EntityRouteIndexer::OPTION_LAST_MODIFICATION_TIMESTAMP, time());
                $this->manager->persist($route);
            }
            $this->manager->flush();
        }

        return true;
    }

    public function renderAction(
        CmsRoutableInterface $contentDocument,
        Request $request,
        ?OrmRoute $route = null,
    ): Response {
        if (null === $route) {
            /**
             * @var ?OrmRoute $route
             */
            $route = $request->attributes->get('routeDocument');
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

        if (true === $route->getOption(EntityRouteIndexer::OPTION_PREVIEW)) {
            $this->denyAccessUnlessGranted(ContentDocumentVoter::PREVIEW, $contentDocument);
        }

        if (!$contentDocument->isOnline()) {
            throw $this->createNotFoundException('Document is not published');
        }

        $breadcrumbItems = $contentDocument->getBreadcrumbItems();
        foreach ($breadcrumbItems as $breadcrumbItem) {
            $this->breadcrumb->addSimpleItem(
                $breadcrumbItem['label'],
                $breadcrumbItem['route']->getPath(),
            );
        }

        $this->twig->addGlobal('resource', $contentDocument);

        $event = $this->eventDispatcher->dispatch(new RouteRenderServiceEvent([
                                                                                  'metadata' => $metadata,
                                                                                  'configuration' => $configuration,
                                                                                  'resource' => $contentDocument,
                                                                                  'route' => $route,
                                                                                  'preview' => $route->getOption(EntityRouteIndexer::OPTION_PREVIEW),
                                                                              ]));

        return $this->render($template, $event->getParameters(), $response);
    }

    /**
     * @return array<int, Metadata|RequestConfiguration|null>
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function contentDocumentAsResource(
        string $model,
        Request $request,
    ): array {
        try {
            /** @var array<string, array<mixed, mixed>> $resources */
            $resources = $this->container->get('parameter_bag')->get('sylius.resources');
        } catch (InvalidArgumentException $exception) {
            return [];
        }
        $metadata = $configuration = null;
        foreach ($resources as $alias => $parameters) {
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
