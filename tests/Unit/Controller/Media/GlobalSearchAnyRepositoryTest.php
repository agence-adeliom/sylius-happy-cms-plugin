<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Controller\Media;

use Adeliom\SyliusHappyCMSPlugin\Controller\Media\MediaController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\IdentityTranslator;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\MediaManagerTestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\PlainRepositoryMedia;

/**
 * The global search works whatever the repository of the media entity.
 */
final class GlobalSearchAnyRepositoryTest extends MediaManagerTestCase
{
    protected function mediaClass(): string
    {
        return PlainRepositoryMedia::class;
    }

    public function testItFindsTheMediasOfAnEntityWithoutThePluginRepository(): void
    {
        $this->saveMedia('door.jpg', $this->saveFolder('products'));

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($this->entityManager);
        $controller = new MediaController($this->manager, $registry, new ParameterBag($this->parameters), new EventDispatcher(), new IdentityTranslator());

        $items = json_decode((string) $controller->globalSearch(new Request(['search' => 'door']))->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertIsArray($items);
        self::assertSame(['products/door.jpg'], array_column($items, 'storage_path'));
    }
}
