<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RedirectRouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RedirectRouteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class RedirectionManager implements RedirectionManagerInterface
{
    public function __construct(
        protected EntityManagerInterface $manager,
        protected ParameterBag $parameterBag,
    ) {
    }

    public function onHttpNotFoundException(ExceptionEvent $event): ?Response
    {
        $response = $event->getResponse();

        // Get the current not found uri
        $uri = $event->getRequest()->getRequestUri();
        $host = $event->getRequest()->getHost();

        $resources = $this->parameterBag->get('sylius.resources');
        $modelClass = $resources['sylius_happy_cms.redirect_route']['classes']['model'] ?? 'Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RedirectRoute';

        if (!is_a($modelClass, RedirectRouteInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf(
                'The model class "%s" must implement "%s".',
                $modelClass,
                RedirectRouteInterface::class
            ));
        }

        /** @var RedirectRouteRepositoryInterface $redirectRouteRepository **/
        $redirectRouteRepository = $this->manager->getRepository($modelClass);

        // A redirect route is already persisted?
        $redirectRoute = $redirectRouteRepository->findByHostAndPath($host, $uri);
        if (null === $redirectRoute) {
            // If not, we can create a new one
            $redirectRoute = $redirectRouteRepository->createNew();
            $redirectRoute->setStaticPrefix($uri);
            $redirectRoute->setHost($host);
            $this->manager->persist($redirectRoute);
            $this->manager->flush();
        }

        if (null !== $redirectRoute->getUri() && $redirectRoute->getUri() !== '') {
            $response = new RedirectResponse(
                $redirectRoute->getUri(),
                $redirectRoute->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND,
            );
        } elseif (null !== $redirectRoute->getRouteTarget()) {
            $routeTarget = $redirectRoute->getRouteTarget();
            $response = new RedirectResponse(
                $routeTarget->getPath(),
                $redirectRoute->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND,
            );
        }

        return $response;
    }
}
