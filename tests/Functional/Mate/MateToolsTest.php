<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Mate;

use Adeliom\SyliusHappyCMSPlugin\Block\AccordionBlockType;
use Adeliom\SyliusHappyCMSPlugin\Mate\Tool\ContentBlocks;
use Adeliom\SyliusHappyCMSPlugin\Mate\Tool\ListBlocks;
use Adeliom\SyliusHappyCMSPlugin\Mate\Tool\MatchUrl;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Sylius\MateExtension\Kernel\HostContainerProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The tools read the public container, as Mate does with the booted host kernel: the services they use must be public.
 * The paths reading the database (matched CMS routes, stored blocks) need fixtures absent from this suite.
 */
final class MateToolsTest extends KernelTestCase
{
    private const PAGE_ALIAS = 'sylius_happy_cms.page';

    #[Test]
    public function it_lists_the_registered_flex_and_shared_blocks(): void
    {
        $items = $this->items((new ListBlocks($this->host()))());

        $accordion = $this->findBlock($items, AccordionBlockType::class);
        self::assertSame('flex', $accordion['kind']);
        self::assertIsString($accordion['name']);
        self::assertIsString($accordion['template']);
        self::assertContains('shared', array_column($items, 'kind'));
    }

    #[Test]
    public function it_filters_the_blocks_by_kind(): void
    {
        $items = $this->items((new ListBlocks($this->host()))(kind: 'shared'));

        self::assertNotSame([], $items);
        foreach ($items as $item) {
            self::assertSame('shared', $item['kind']);
        }
    }

    #[Test]
    public function it_rejects_an_unknown_kind(): void
    {
        $result = (new ListBlocks($this->host()))(kind: 'unknown');

        self::assertIsArray($result['error'] ?? null);
        self::assertSame('invalid_kind', $result['error']['code']);
    }

    #[Test]
    public function it_lists_the_blocks_allowed_in_the_flexible_content_fields_of_a_resource(): void
    {
        $items = $this->items((new ListBlocks($this->host()))(self::PAGE_ALIAS));

        $accordion = $this->findBlock($items, AccordionBlockType::class);
        self::assertSame(['content'], $accordion['fields']);
        foreach ($items as $item) {
            self::assertSame('flex', $item['kind']);
        }
    }

    #[Test]
    public function it_reports_an_alias_that_is_not_an_easy_crud_resource(): void
    {
        $host = $this->host();

        foreach ([(new ListBlocks($host))('app.unknown'), (new ContentBlocks($host))('app.unknown')] as $result) {
            self::assertSame([], $result['items'] ?? null);
            self::assertIsString($result['note'] ?? null);
        }
    }

    #[Test]
    public function it_matches_a_url_served_by_a_symfony_route(): void
    {
        $items = $this->items((new MatchUrl($this->host()))('/admin/login'));

        self::assertCount(1, $items);
        self::assertTrue($items[0]['matched']);
        self::assertSame('symfony', $items[0]['router']);
        self::assertSame('sylius_admin_login', $items[0]['route']);
        self::assertArrayNotHasKey('content', $items[0]);
    }

    private function host(): HostContainerProvider
    {
        $container = self::bootKernel()->getContainer();

        return new class($container) implements HostContainerProvider {
            public function __construct(
                private readonly ContainerInterface $container,
            ) {
            }

            public function getContainer(): ContainerInterface
            {
                return $this->container;
            }

            public function getRootDir(): string
            {
                return '';
            }
        };
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return list<array<mixed>>
     */
    private function items(array $result): array
    {
        self::assertArrayNotHasKey('error', $result, json_encode($result['error'] ?? null) ?: '');
        self::assertIsArray($result['items'] ?? null);

        $items = [];
        foreach ($result['items'] as $item) {
            self::assertIsArray($item);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param list<array<mixed>> $items
     *
     * @return array<mixed>
     */
    private function findBlock(array $items, string $type): array
    {
        foreach ($items as $item) {
            if (($item['block_type'] ?? null) === $type) {
                return $item;
            }
        }

        self::fail(sprintf('The block "%s" is not listed.', $type));
    }
}
