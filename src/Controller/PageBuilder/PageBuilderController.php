<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\PageBuilder;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageBuilderController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $parameterBag,
        private readonly LocaleProviderInterface $localeProvider,
    ) {
    }

    public function builderAction(Request $request, string $resource, int $id): Response
    {
        // Resolve the entity class from the resource name (validates interfaces)
        $entityClass = $this->resolveEntityClass($resource);

        // Load the entity
        $entity = $this->loadEntity($entityClass, $id);

        // Get the current locale from query parameter or use default locale
        $locale = $request->query->get('locale', $request->getLocale());

        // Ensure the locale is a string
        if (!is_string($locale)) {
            $locale = $this->localeProvider->getDefaultLocaleCode();
        }

        // Get available locales
        $availableLocales = $this->localeProvider->getAvailableLocalesCodes();

        // Validate that the selected locale is available
        if (!in_array($locale, $availableLocales, true)) {
            $locale = $request->getLocale();
        }

        // Get preview content blocks for the entity (already validated as ContentEditableInterface)
        $contentBlocks = $entity->getPreviewContentBlocks($locale);

        return $this->render('@SyliusHappyCMSPlugin/admin/page_builder/index.html.twig', [
            'entity' => $entity,
            'resource' => $resource,
            'contentBlocks' => $contentBlocks,
            'locale' => $locale,
            'availableLocales' => $availableLocales,
        ]);
    }

    /**
     * Resolve the entity class from the resource name using Sylius resources configuration.
     *
     * @return class-string<ContentEditableInterface>
     */
    private function resolveEntityClass(string $resource): string
    {
        // Get Sylius resources configuration
        $resources = $this->parameterBag->get('sylius.resources');

        if (!is_array($resources)) {
            throw new \RuntimeException('No Sylius resources found in configuration.');
        }

        // Check if resource exists in configuration
        if (!isset($resources[$resource])) {
            throw new NotFoundHttpException(
                sprintf('Resource "%s" not found in Sylius configuration.', $resource),
            );
        }

        $resourceConfig = $resources[$resource];

        if (!is_array($resourceConfig) || !isset($resourceConfig['classes']['model'])) {
            throw new NotFoundHttpException(
                sprintf('Invalid resource configuration for "%s".', $resource),
            );
        }

        /** @var class-string<ContentEditableInterface> $entityClass */
        $entityClass = $resourceConfig['classes']['model'];

        if (!class_exists($entityClass)) {
            throw new NotFoundHttpException(
                sprintf('Entity class "%s" not found for resource "%s".', $entityClass, $resource),
            );
        }

        // Validate that the entity implements required interfaces
        $reflection = new \ReflectionClass($entityClass);

        if (!$reflection->implementsInterface(ContentEditableInterface::class)) {
            throw new AccessDeniedHttpException(
                sprintf('Entity "%s" does not implement ContentEditableInterface.', $entityClass),
            );
        }

        if (!$reflection->implementsInterface(CmsRoutableInterface::class)) {
            throw new AccessDeniedHttpException(
                sprintf('Entity "%s" does not implement CmsRoutableInterface.', $entityClass),
            );
        }

        if (!$reflection->implementsInterface(ResourceInterface::class)) {
            throw new AccessDeniedHttpException(
                sprintf('Entity "%s" does not implement ResourceInterface.', $entityClass),
            );
        }

        return $entityClass;
    }

    /**
     * Load an entity by its class and ID.
     *
     * @param class-string<ContentEditableInterface> $entityClass
     */
    private function loadEntity(string $entityClass, int $id): ContentEditableInterface
    {
        $repository = $this->entityManager->getRepository($entityClass);
        $entity = $repository->find($id);

        if (null === $entity) {
            throw new NotFoundHttpException(
                sprintf('Entity of type "%s" with ID "%d" not found', $entityClass, $id),
            );
        }

        if (!$entity instanceof ContentEditableInterface) {
            throw new NotFoundHttpException(
                sprintf('Entity of type "%s" must implement ContentEditableInterface', $entityClass),
            );
        }

        return $entity;
    }
}
