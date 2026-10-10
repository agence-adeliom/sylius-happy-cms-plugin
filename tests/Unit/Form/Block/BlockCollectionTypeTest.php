<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Form\Block;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Form\Block\BlockCollectionType;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Form\Block\Fixtures\RestrictedBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Form\Block\Fixtures\TitleBlock;

/**
 * The flexible content collection edits a JSON list of blocks (`{block_type, block_published, position, ...fields}`).
 * The admin script writes the display order into the hidden `position` fields and names new entries with a random
 * id; the saved value must stay a list in display order.
 */
final class BlockCollectionTypeTest extends TestCase
{
    private FormFactoryInterface $factory;

    private TitleBlock $title;

    private RestrictedBlock $text;

    protected function setUp(): void
    {
        $this->title = $this->block(TitleBlock::class);
        $this->text = $this->block(RestrictedBlock::class);

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addTypes([new BlockCollectionType(), $this->title, $this->text])
            ->getFormFactory();
    }

    public function testAStoredListIsEditedWithoutError(): void
    {
        $form = $this->collection([$this->titleData('A', 0), $this->titleData('B', 1)]);

        self::assertSame([0, 1], array_keys($form->all()));
        self::assertSame('A', $form->get('0')->get('title')->getData());
    }

    public function testAStoredObjectWithNumericKeysIsEditedWithoutError(): void
    {
        /** @var array<int, array<string, mixed>> $content */
        $content = json_decode('{"1":' . json_encode($this->titleData('A', 0)) . ',"2":' . json_encode($this->titleData('B', 1)) . '}', true);

        $form = $this->collection($content);
        $form->submit([
            '1' => $this->submitted($this->titleData('A', 5)),
            '2' => $this->submitted($this->titleData('B', 6)),
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertSame(['A', 'B'], $this->titles($form->getData()));
        self::assertTrue(array_is_list($form->getData()));
    }

    public function testTheRowsAreDisplayedInPositionOrder(): void
    {
        $form = $this->collection([$this->titleData('Second', 1), $this->titleData('First', 0)]);

        self::assertSame([1, 0], array_keys($form->all()));
    }

    public function testReorderingKeepsTheNewOrderAndSavesAList(): void
    {
        $form = $this->collection([$this->titleData('A', 0), $this->titleData('B', 1), $this->titleData('C', 2)]);

        // the admin script numbers the positions from the number of blocks
        $form->submit([
            '0' => $this->submitted($this->titleData('A', 5)),
            '1' => $this->submitted($this->titleData('B', 3)),
            '2' => $this->submitted($this->titleData('C', 4)),
        ]);

        $data = $form->getData();
        self::assertSame(['B', 'C', 'A'], $this->titles($data));
        self::assertTrue(array_is_list($data));
        self::assertSame([0, 1, 2], array_column($data, 'position'));
        self::assertStringStartsWith('[', (string) json_encode($data));
    }

    public function testDeletingABlockSavesAList(): void
    {
        $form = $this->collection([$this->titleData('A', 0), $this->titleData('B', 1), $this->titleData('C', 2)]);

        $form->submit([
            '0' => $this->submitted($this->titleData('A', 2)),
            '2' => $this->submitted($this->titleData('C', 3)),
        ]);

        $data = $form->getData();
        self::assertSame(['A', 'C'], $this->titles($data));
        self::assertTrue(array_is_list($data), 'A list, not {"0":…,"2":…}.');
        self::assertSame([0, 1], array_column($data, 'position'));
    }

    public function testAddingABlockInsertsItAtItsPosition(): void
    {
        $form = $this->collection([$this->titleData('A', 0), $this->titleData('C', 1)]);

        $form->submit([
            '0' => $this->submitted($this->titleData('A', 3)),
            '1' => $this->submitted($this->titleData('C', 5)),
            'f3a1-9bc' => $this->submitted($this->titleData('B', 4)),
        ]);

        $data = $form->getData();
        self::assertSame(['A', 'B', 'C'], $this->titles($data));
        self::assertTrue(array_is_list($data));
        self::assertSame(TitleBlock::class, $data[1]['block_type']);
    }

    public function testTheFirstBlockAtPositionZeroStaysFirst(): void
    {
        $form = $this->collection([$this->titleData('A', 0), $this->titleData('B', 1)]);

        $form->submit([
            '0' => $this->submitted($this->titleData('A', 0)),
            '1' => $this->submitted($this->titleData('B', 1)),
        ]);

        self::assertSame(['A', 'B'], $this->titles($form->getData()));
        self::assertSame([0, 1], array_column($form->getData(), 'position'));
    }

    public function testBlocksWithoutPositionGoAfterTheOthersInSubmittedOrder(): void
    {
        $form = $this->collection([]);

        $form->submit([
            'x1' => $this->submitted($this->titleData('No position 1', '')),
            'x2' => $this->submitted($this->titleData('Positioned', 7)),
            'x3' => $this->submitted($this->titleData('No position 2', '')),
        ]);

        self::assertSame(['Positioned', 'No position 1', 'No position 2'], $this->titles($form->getData()));
        self::assertSame([0, 1, 2], array_column($form->getData(), 'position'));
    }

    public function testASubmittedRowWithoutBlockTypeIsIgnored(): void
    {
        $form = $this->collection([$this->titleData('A', 0)]);

        $form->submit([
            '0' => $this->submitted($this->titleData('A', 0)),
            'junk' => ['position' => '1'],
        ]);

        self::assertSame(['A'], $this->titles($form->getData()));
    }

    public function testANewBlockIsSavedWithItsFieldsAndItsPosition(): void
    {
        $form = $this->collection([]);

        $form->submit(['a1b2-c3d' => $this->submitted($this->titleData('Filled before saving', 0))]);

        self::assertSame([['block_type' => TitleBlock::class, 'block_published' => '1', 'position' => 0, 'title' => 'Filled before saving']], $form->getData());
    }

    /**
     * @param array<array-key, array<string, mixed>> $data
     * @param array<string, mixed> $options
     */
    private function collection(array $data, array $options = []): FormInterface
    {
        return $this->factory->create(BlockCollectionType::class, $data, array_merge([
            'allow_add' => true,
            'allow_delete' => true,
            'allow_drag' => true,
            'blocks' => [TitleBlock::class => $this->title, RestrictedBlock::class => $this->text],
        ], $options));
    }

    /**
     * @return array<string, mixed>
     */
    private function titleData(string $title, int|string $position): array
    {
        return ['block_type' => TitleBlock::class, 'block_published' => '1', 'position' => $position, 'title' => $title];
    }

    /**
     * Values as posted by the browser.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    private function submitted(array $data): array
    {
        return array_map(static fn (mixed $value): string => \is_scalar($value) ? (string) $value : '', $data);
    }

    /**
     * @return list<mixed>
     */
    private function titles(mixed $data): array
    {
        self::assertIsArray($data);

        return array_values(array_column($data, 'title'));
    }

    /**
     * @template T of AbstractBlock
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function block(string $class): AbstractBlock
    {
        return new $class(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(TranslatorInterface::class),
            $this->createStub(FormFactoryInterface::class),
            new Packages(),
        );
    }
}
