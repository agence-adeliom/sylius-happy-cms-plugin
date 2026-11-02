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

/**
 * GET media.global_search: the media of every folder whose name matches, in one query.
 */
final class GlobalSearchTest extends MediaManagerTestCase
{
    protected function mediaConfig(): array
    {
        return ['base_url' => 'https://cdn.example.com/media'];
    }

    public function testItFindsTheMatchingMediasOfEveryFolder(): void
    {
        $products = $this->saveFolder('products');
        $doors = $this->saveFolder('doors', $products);
        $this->saveMedia('sunset-door.jpg', $doors);
        $this->saveMedia('sunset.png');
        $this->saveMedia('window.jpg', $products);

        $items = $this->search('sunset');

        self::assertSame(['sunset', 'sunset-door'], array_column($items, 'name'));
        $door = $items[1];
        self::assertSame('products/doors/sunset-door.jpg', $door['storage_path']);
        self::assertSame('products/doors', $door['dir_path']);
        self::assertSame('https://cdn.example.com/media/products/doors/sunset-door.jpg', $door['path']);
        self::assertSame('image/jpeg', $door['type']);
        self::assertSame('/', $items[0]['dir_path']);
    }

    public function testWithoutSearchTermItListsEveryMedia(): void
    {
        $products = $this->saveFolder('products');
        $this->saveMedia('door.jpg', $products);
        $this->saveMedia('window.jpg');

        self::assertCount(2, $this->search(null));
    }

    public function testTheSearchTermIsNotAPattern(): void
    {
        $this->saveMedia('door.jpg');

        self::assertSame([], $this->search('%'));
        self::assertSame([], $this->search('d_or'));
    }

    public function testIgnoredFilesAreLeftOut(): void
    {
        $this->saveMedia('.hidden.jpg');
        $this->saveMedia('visible.jpg');

        self::assertSame(['visible'], array_column($this->search(null), 'name'));
    }

    public function testItRunsOneMediaQuery(): void
    {
        $products = $this->saveFolder('products');
        foreach (range(1, 5) as $i) {
            $this->saveMedia(sprintf('door-%d.jpg', $i), $products);
        }
        $this->entityManager->clear();
        $this->queries->queries = [];

        self::assertCount(5, $this->search('door'));
        self::assertCount(1, $this->queries->selectsFrom('managed_media'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function search(?string $term): array
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($this->entityManager);
        $controller = new MediaController($this->manager, $registry, new ParameterBag($this->parameters), new EventDispatcher(), new IdentityTranslator());

        $response = $controller->globalSearch(new Request(null === $term ? [] : ['search' => $term]));

        /** @var list<array<string, mixed>> $items */
        $items = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($items);

        return $items;
    }
}
