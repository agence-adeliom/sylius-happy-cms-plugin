<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin;

use Adeliom\SyliusHappyCMSPlugin\Doctrine\MediaReferenceType;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SyliusHappyCMSPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function boot(): void
    {
        $container = $this->container;
        if (null === $container) {
            return;
        }

        MediaReferenceType::setReferenceFactory(static function (int $id) use ($container): MediaInterface {
            /** @var class-string<MediaInterface> $mediaClass */
            $mediaClass = $container->getParameter('sylius_happy_cms.media.media_entity');
            /** @var ManagerRegistry $doctrine */
            $doctrine = $container->get('doctrine');
            $entityManager = $doctrine->getManagerForClass($mediaClass);
            if (!$entityManager instanceof EntityManagerInterface) {
                throw new \LogicException(sprintf('No Doctrine ORM entity manager handles the media entity "%s" (sylius_happy_cms.media.media_entity).', $mediaClass));
            }

            /** @var MediaInterface $reference */
            $reference = $entityManager->getReference($mediaClass, $id);

            return $reference;
        });
    }

    public function shutdown(): void
    {
        MediaReferenceType::setReferenceFactory(null);
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
