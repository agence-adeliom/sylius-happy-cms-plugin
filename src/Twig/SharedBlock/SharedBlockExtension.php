<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\Helper;
use Adeliom\SyliusHappyCMSPlugin\Repository\SharedBlock\SharedBlockRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SharedBlockExtension extends AbstractExtension
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('happy_cms_shared_block_render', [Helper::class, 'renderBlock'], ['is_safe' => ['js', 'html'], 'needs_context' => true, 'needs_environment' => true]),
            new TwigFunction('happy_cms_shared_block_assets', [Helper::class, 'includeAssets'], ['is_safe' => ['js', 'html'], 'needs_context' => true, 'needs_environment' => true]),
            new TwigFunction('happy_cms_get_shared_block_by_key', $this->getByKey(...)),
        ];
    }

    public function getByKey(string $key): ?int
    {
        try {
            $resourceName = 'sylius_happy_cms.shared_block';
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

            if (null === $modelClass || !is_a($modelClass, SharedBlockInterface::class, true)) {
                throw new \InvalidArgumentException(sprintf('The resource "%s" must implement "%s".', $resourceName, SharedBlockInterface::class));
            }

            /** @var SharedBlockRepositoryInterface|null $repository */
            $repository = $this->manager->getRepository($modelClass);

            if (null === $repository || !method_exists($repository, 'getByKey')) {
                throw new \InvalidArgumentException(sprintf('The resource "%s" repository must have a method "%s".', $resourceName, 'getByKey'));
            }

            /** @var SharedBlockInterface|null $object */
            $object = $repository->getByKey($key);
            if (null !== $object) {
                return $object->getId();
            }

            return null;
        } catch (\Exception $exception) {
            return null;
        }
    }
}
