<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Controller\Media;

use Adeliom\SyliusHappyCMSPlugin\Controller\Media\MediaController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\IdentityTranslator;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\ManagedFolder;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\MediaManagerTestCase;

/**
 * GET media.global_search: the folders and media whose name matches, in the whole library or under a folder, paginated.
 */
final class GlobalSearchTest extends MediaManagerTestCase
{
    protected function mediaConfig(): array
    {
        return ['base_url' => 'https://cdn.example.com/media', 'pagination_amount' => 4];
    }

    public function testItFindsTheMatchingFoldersThenMediasOfEveryFolder(): void
    {
        $products = $this->saveFolder('Produits');
        $doors = $this->saveFolder('Portes', $products);
        $this->saveFolder('Fenêtres', $products);
        $door = $this->saveMedia('porte-alu.jpg', $doors);
        $this->saveMedia('porte.png');
        $this->saveMedia('fenetre.jpg', $products);

        $page = $this->search(['q' => 'porte']);

        self::assertSame(3, $page['total']);
        self::assertSame([['folder', 'Portes'], ['image/jpeg', 'porte'], ['image/jpeg', 'porte-alu']], array_map(
            static fn (array $item): array => [$item['type'], $item['name']],
            $page['data'],
        ));

        [$folder, $rootMedia, $media] = $page['data'];
        self::assertSame([
            'id' => $doors->getId(),
            'name' => 'Portes',
            'type' => 'folder',
            'path' => 'produits/portes',
            'storage_path' => 'produits/portes',
            'folder_id' => $products->getId(),
            'dir_path' => 'produits',
            'breadcrumb' => [['id' => $products->getId(), 'name' => 'Produits']],
        ], $folder);

        self::assertNull($rootMedia['folder_id']);
        self::assertSame('/', $rootMedia['dir_path']);
        self::assertSame([], $rootMedia['breadcrumb']);

        self::assertSame($door->getId(), $media['id']);
        self::assertSame('https://cdn.example.com/media/produits/portes/porte-alu.jpg', $media['path']);
        self::assertSame('produits/portes/porte-alu.jpg', $media['storage_path']);
        self::assertSame([], $media['metas']);
        self::assertSame($doors->getId(), $media['folder_id']);
        self::assertSame('produits/portes', $media['dir_path']);
        self::assertSame([
            ['id' => $products->getId(), 'name' => 'Produits'],
            ['id' => $doors->getId(), 'name' => 'Portes'],
        ], $media['breadcrumb']);
    }

    public function testTheTypeKeepsTheMediasOrTheFolders(): void
    {
        $this->saveFolder('portes');
        $this->saveMedia('porte.jpg');

        self::assertSame(['image/jpeg'], array_column($this->search(['q' => 'porte', 'type' => 'media'])['data'], 'type'));
        self::assertSame(['folder'], array_column($this->search(['q' => 'porte', 'type' => 'folder'])['data'], 'type'));
        self::assertCount(2, $this->search(['q' => 'porte', 'type' => 'all'])['data']);
    }

    public function testAnUnknownTypeIsRejected(): void
    {
        self::assertSame(400, $this->call(['q' => 'porte', 'type' => 'images'])->getStatusCode());
    }

    public function testQIsReadFirstThenSearch(): void
    {
        $this->saveMedia('porte.jpg');
        $this->saveMedia('fenetre.jpg');

        self::assertSame(['porte'], $this->names(['q' => 'porte', 'search' => 'fenetre']));
        self::assertSame(['fenetre'], $this->names(['search' => 'fenetre']));
        self::assertSame(['fenetre'], $this->names(['q' => '  ', 'search' => 'fenetre']));
    }

    public function testATermShorterThanTwoCharactersReturnsAnEmptyPageWithoutQuery(): void
    {
        $this->saveMedia('porte.jpg');
        $this->queries->queries = [];

        foreach ([[], ['q' => 'p'], ['q' => ' p ']] as $query) {
            $page = $this->search($query);
            self::assertSame([], $page['data']);
            self::assertSame(0, $page['total']);
        }
        self::assertSame([], $this->queries->queries);
    }

    public function testTheSearchTermIsNotAPattern(): void
    {
        $this->saveFolder('portes');
        $this->saveMedia('porte.jpg');

        self::assertSame([], $this->names(['q' => '%%']));
        self::assertSame([], $this->names(['q' => 'p_rte']));
    }

    public function testIgnoredFilesAndFoldersAreLeftOut(): void
    {
        // Like the listing, `ignore_files` is matched against the path, made of slugs.
        $hidden = new ManagedFolder();
        $hidden->setSlug('.portes');
        $hidden->setName('.portes');
        $this->entityManager->persist($hidden);
        $this->entityManager->flush();
        $this->saveMedia('.porte-cachee.jpg');
        $this->saveMedia('porte.jpg');

        self::assertSame(['porte'], $this->names(['q' => 'porte']));
    }

    public function testTheFolderLimitsTheSearchToItsSubFolders(): void
    {
        $products = $this->saveFolder('produits');
        $doors = $this->saveFolder('portes', $products);
        $this->saveFolder('portes interieures', $doors);
        $this->saveMedia('porte-a.jpg', $products);
        $this->saveMedia('porte-b.jpg', $doors);
        $this->saveFolder('portes', $this->saveFolder('archives'));
        $this->saveMedia('porte-c.jpg');

        $page = $this->search(['q' => 'porte', 'folder' => (string) $products->getId()]);
        self::assertSame(['portes', 'portes interieures', 'porte-a', 'porte-b'], array_column($page['data'], 'name'));

        // The scope folder itself is not a result.
        self::assertSame(['portes interieures', 'porte-b'], $this->names(['q' => 'porte', 'folder' => (string) $doors->getId()]));
    }

    public function testAnUnknownOrInvalidFolderIsRejected(): void
    {
        self::assertSame(404, $this->call(['q' => 'porte', 'folder' => '999'])->getStatusCode());
        self::assertSame(400, $this->call(['q' => 'porte', 'folder' => 'abc'])->getStatusCode());
    }

    public function testTheTypesFilterTheMediasAndKeepTheFolders(): void
    {
        $this->saveFolder('portes');
        $this->saveMedia('porte.jpg');
        $this->saveMedia('porte-notice.pdf', null, 'application/pdf');
        $this->saveMedia('porte-archive.zip', null, 'application/zip');

        self::assertSame(['portes', 'porte'], $this->names(['q' => 'porte', 'types' => ['images']]));
        self::assertSame(['portes', 'porte-notice'], $this->names(['q' => 'porte', 'types' => ['pdf']]));
        self::assertSame(['portes', 'porte-archive'], $this->names(['q' => 'porte', 'types' => ['archives']]));
        self::assertSame(['portes', 'porte', 'porte-archive', 'porte-notice'], $this->names(['q' => 'porte', 'types' => ['all']]));
        self::assertSame(['portes'], $this->names(['q' => 'porte', 'types' => ['folder']]));
    }

    public function testItPaginatesTheFoldersThenTheMedias(): void
    {
        $folder = $this->saveFolder('portes');
        $this->saveFolder('portes bis');
        foreach (range(1, 5) as $i) {
            $this->saveMedia(sprintf('porte-%d.jpg', $i), $folder);
        }

        $first = $this->search(['q' => 'porte', 'type' => 'all', 'types' => ['image']]);
        self::assertSame(['portes', 'portes bis', 'porte-1', 'porte-2'], array_column($first['data'], 'name'));
        self::assertSame(7, $first['total']);
        self::assertSame(2, $first['last_page']);
        self::assertSame(4, $first['per_page']);
        self::assertSame([1, 4], [$first['from'], $first['to']]);
        self::assertNull($first['prev_page_url']);
        self::assertSame('/?q=porte&types%5B0%5D=image&page=2', $first['next_page_url']);

        $second = $this->search(['q' => 'porte', 'page' => '2']);
        self::assertSame(['porte-3', 'porte-4', 'porte-5'], array_column($second['data'], 'name'));
        self::assertSame([5, 7], [$second['from'], $second['to']]);
        self::assertNull($second['next_page_url']);
        self::assertSame('/?q=porte&page=1', $second['prev_page_url']);

        self::assertSame([], $this->search(['q' => 'porte', 'page' => '3'])['data']);
    }

    public function testTheQueryCountDoesNotDependOnTheResults(): void
    {
        $parent = $this->saveFolder('produits');
        foreach (range(1, 3) as $i) {
            $folder = $this->saveFolder(sprintf('dossier %d', $i), $this->saveFolder(sprintf('niveau %d', $i), $parent));
            $this->saveMedia(sprintf('porte-%d.jpg', $i), $folder);
        }
        $this->entityManager->clear();
        $this->queries->queries = [];

        self::assertCount(3, $this->search(['q' => 'porte'])['data']);
        self::assertCount(2, $this->queries->selectsFrom('managed_media'));
        self::assertCount(4, $this->queries->queries);
    }

    /**
     * @param array<string, mixed> $query
     */
    private function call(array $query): JsonResponse
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($this->entityManager);
        $controller = new MediaController($this->manager, $registry, new ParameterBag($this->parameters), new EventDispatcher(), new IdentityTranslator());

        return $controller->globalSearch(new Request($query));
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array{data: list<array<string, mixed>>, total: int, per_page: int, last_page: int, from: int|null, to: int|null, next_page_url: string|null, prev_page_url: string|null}
     */
    private function search(array $query): array
    {
        $response = $this->call($query);
        self::assertSame(200, $response->getStatusCode(), (string) $response->getContent());

        $content = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($content);
        self::assertIsArray($content['items'] ?? null);

        /** @var array{data: list<array<string, mixed>>, total: int, per_page: int, last_page: int, from: int|null, to: int|null, next_page_url: string|null, prev_page_url: string|null} $page */
        $page = $content['items'];

        return $page;
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return list<mixed>
     */
    private function names(array $query): array
    {
        return array_column($this->search($query)['data'], 'name');
    }
}
