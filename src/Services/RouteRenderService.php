<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services;

use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Security\ContentDocumentVoter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactory;
use Sylius\Resource\Metadata\Metadata;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Route as OrmRoute;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class RouteRenderService extends AbstractController
{
    public function __construct(
        protected RouterInterface $router,
        protected RequestConfigurationFactory $requestConfigurationFactory,
        protected Environment $twig,
    ) {
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
                    $contentDocument,
                    $request,
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

        $this->twig->addGlobal('resource', $contentDocument);

        return $this->render($template, [
            'metadata' => $metadata,
            'configuration' => $configuration,
            'resource' => $contentDocument,
            'route' => $route,
            'preview' => $route->getOption(EntityRouteIndexer::OPTION_PREVIEW),
        ]);
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
