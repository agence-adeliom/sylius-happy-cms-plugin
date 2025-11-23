<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RouteExtension extends AbstractExtension
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly RouterInterface $router,
        private readonly ParameterBagInterface $parameterBag,
        private readonly RequestStack $requestStack,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('happy_cms_path', $this->getPath(...)),
            new TwigFunction('happy_cms_path_by_seo_key', $this->getPathBySeoKey(...)),
            new TwigFunction('happy_cms_path_by_template', $this->getPathByTemplate(...)),
            new TwigFunction('happy_cms_path_by_key', $this->getPathByKey(...)),
            new TwigFunction('happy_cms_path_by_id', $this->getPathById(...)),
        ];
    }

    public function getPath(CmsRoutableInterface $object): ?string
    {
        try {
            return $this->router->generate(RouteObjectInterface::OBJECT_BASED_ROUTE_NAME, [
                RouteObjectInterface::ROUTE_OBJECT => $object->getOnlineRoute(),
            ]);
        } catch (NoResultException | NonUniqueResultException $e) {
            return '';
        }
    }

    public function getPathBySeoKey(string $key, string $resourceName = 'sylius_happy_cms.page', ?string $locale = null): ?string
    {
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
            $modelClass = $resources[$resourceName]['classes']['model'] ?? null;

            if (null === $modelClass || !is_a($modelClass, CmsRoutableInterface::class, true)) {
                throw new \InvalidArgumentException(sprintf('The resource "%s" must implement "%s".', $resourceName, CmsRoutableInterface::class));
            }

            $repository = $this->manager->getRepository($modelClass);

            if (!method_exists($repository, 'getBySeoKey')) {
                throw new \InvalidArgumentException(sprintf('The resource "%s" repository must have a method "%s".', $resourceName, 'getBySeoKey'));
            }

            if (null === $locale) {
                $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en_US';
            }

            /** @var CmsRoutableInterface|null $object */
            $object = $repository->getBySeoKey($key, $locale);
            if (null !== $object) {
                return $this->router->generate(RouteObjectInterface::OBJECT_BASED_ROUTE_NAME, [
                    RouteObjectInterface::ROUTE_OBJECT => $object->getOnlineRoute(),
                ]);
            }

            return '';
        } catch (NoResultException | NonUniqueResultException $e) {
            return '';
        }
    }

    public function getPathByKey(string $key, string $resourceName = 'sylius_happy_cms.page'): ?string
    {
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
            $modelClass = $resources[$resourceName]['classes']['model'] ?? null;

            if (null === $modelClass || !is_a($modelClass, CmsRoutableInterface::class, true)) {
                throw new \InvalidArgumentException(sprintf('The resource "%s" must implement "%s".', $resourceName, CmsRoutableInterface::class));
            }

            $repository = $this->manager->getRepository($modelClass);

            if (!method_exists($repository, 'getByKey')) {
                throw new \InvalidArgumentException(sprintf('The resource "%s" repository must have a method "%s".', $resourceName, 'getByKey'));
            }

            /** @var CmsRoutableInterface|null $object */
            $object = $repository->getByKey($key);
            if (null !== $object) {
                return $this->router->generate(RouteObjectInterface::OBJECT_BASED_ROUTE_NAME, [
                    RouteObjectInterface::ROUTE_OBJECT => $object->getOnlineRoute(),
                ]);
            }

            return '';
        } catch (NoResultException | NonUniqueResultException $e) {
            return '';
        }
    }

    public function getPathByTemplate(
        string $key,
        string $resourceName = 'sylius_happy_cms.page',
        ?string $locale = null,
        ?ChannelInterface $channel = null,
    ): ?string {
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
            $modelClass = $resources[$resourceName]['classes']['model'] ?? null;

            if (null === $modelClass || !is_a($modelClass, CmsRoutableInterface::class, true)) {
                throw new \InvalidArgumentException(sprintf('The resource "%s" must implement "%s".', $resourceName, CmsRoutableInterface::class));
            }

            $repository = $this->manager->getRepository($modelClass);

            if (!method_exists($repository, 'getByTemplate')) {
                throw new \InvalidArgumentException(sprintf('The resource "%s" repository must have a method "%s".', $resourceName, 'getByTemplate'));
            }

            if (null === $locale) {
                $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en_US';
            }

            if (null === $channel) {
                $channel = $this->channelContext->getChannel();
            }

            /** @var CmsRoutableInterface|null $object */
            $object = $repository->getByTemplate($key, $locale, $channel);
            if (null !== $object) {
                return $this->router->generate(RouteObjectInterface::OBJECT_BASED_ROUTE_NAME, [
                    RouteObjectInterface::ROUTE_OBJECT => $object->getOnlineRoute(),
                ]);
            }

            return '';
        } catch (NoResultException | NonUniqueResultException $e) {
            return '';
        }
    }

    public function getPathById(int $id, string $resourceName = 'sylius_happy_cms.page'): ?string
    {
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
            $modelClass = $resources[$resourceName]['classes']['model'] ?? null;

            if (null === $modelClass || !is_a($modelClass, CmsRoutableInterface::class, true)) {
                throw new \InvalidArgumentException(sprintf('The resource "%s" must implement "%s".', $resourceName, CmsRoutableInterface::class));
            }

            $repository = $this->manager->getRepository($modelClass);

            /** @var CmsRoutableInterface|null $object */
            $object = $repository->find($id);
            if (null !== $object) {
                return $this->router->generate(RouteObjectInterface::OBJECT_BASED_ROUTE_NAME, [
                    RouteObjectInterface::ROUTE_OBJECT => $object->getOnlineRoute(),
                ]);
            }

            return '';
        } catch (NoResultException | NonUniqueResultException $e) {
            return '';
        }
    }
}
