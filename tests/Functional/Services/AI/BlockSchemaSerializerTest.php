<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Services\AI;

use Adeliom\SyliusHappyCMSPlugin\Services\AI\BlockSchemaSerializer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BlockSchemaSerializerTest extends KernelTestCase
{
    private BlockSchemaSerializer $blockSchemaSerializer;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $this->blockSchemaSerializer = $container->get(BlockSchemaSerializer::class);
    }

    public function testSerializeBlocksReturnsArray(): void
    {
        $result = $this->blockSchemaSerializer->serializeBlocks();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('blocks', $result);
        $this->assertArrayHasKey('total_count', $result);
    }

    public function testSerializeBlocksReturnsBlocksArray(): void
    {
        $result = $this->blockSchemaSerializer->serializeBlocks();

        $this->assertIsArray($result['blocks']);
        $this->assertGreaterThan(0, count($result['blocks']), 'Should have at least one block registered');
    }

    public function testSerializesBlockWithCorrectStructure(): void
    {
        $result = $this->blockSchemaSerializer->serializeBlocks();
        $blocks = $result['blocks'];

        $this->assertNotEmpty($blocks, 'Blocks array should not be empty');

        $firstBlock = $blocks[0];

        // Check required keys
        $this->assertArrayHasKey('namespace', $firstBlock);
        $this->assertArrayHasKey('name', $firstBlock);
        $this->assertArrayHasKey('fields', $firstBlock);

        // Check types
        $this->assertIsString($firstBlock['namespace']);
        $this->assertIsString($firstBlock['name']);
        $this->assertIsArray($firstBlock['fields']);
    }

    public function testSerializesBlockFieldsWithCorrectStructure(): void
    {
        $result = $this->blockSchemaSerializer->serializeBlocks();
        $blocks = $result['blocks'];

        // Find a block that has fields
        $blockWithFields = null;
        foreach ($blocks as $block) {
            if (!empty($block['fields'])) {
                $blockWithFields = $block;
                break;
            }
        }

        $this->assertNotNull($blockWithFields, 'Should have at least one block with fields');

        $firstField = $blockWithFields['fields'][0];

        // Check required field keys
        $this->assertArrayHasKey('name', $firstField);
        $this->assertArrayHasKey('type', $firstField);
        $this->assertArrayHasKey('required', $firstField);
        $this->assertArrayHasKey('label', $firstField);

        // Check field types
        $this->assertIsString($firstField['name']);
        $this->assertIsString($firstField['type']);
        $this->assertIsBool($firstField['required']);
    }

    public function testToJsonReturnsValidJson(): void
    {
        $json = $this->blockSchemaSerializer->toJson();

        $this->assertIsString($json);
        $this->assertNotEmpty($json);

        // Verify it's valid JSON
        $decoded = json_decode($json, true);
        $this->assertNotNull($decoded, 'Should return valid JSON');
        $this->assertArrayHasKey('blocks', $decoded);
        $this->assertArrayHasKey('total_count', $decoded);
    }

    public function testTotalCountMatchesBlocksArrayLength(): void
    {
        $result = $this->blockSchemaSerializer->serializeBlocks();

        $this->assertEquals(
            count($result['blocks']),
            $result['total_count'],
            'total_count should match the number of blocks in the blocks array'
        );
    }

    public function testExcludesInternalFields(): void
    {
        $result = $this->blockSchemaSerializer->serializeBlocks();
        $blocks = $result['blocks'];

        foreach ($blocks as $block) {
            $fieldNames = array_column($block['fields'], 'name');

            // Internal fields should be excluded
            $this->assertNotContains('block_type', $fieldNames);
            $this->assertNotContains('block_published', $fieldNames);
            $this->assertNotContains('position', $fieldNames);
        }
    }

    public function testSerializesNestedFields(): void
    {
        $result = $this->blockSchemaSerializer->serializeBlocks();
        $blocks = $result['blocks'];

        // Look for a block with nested fields (like ButtonEmbeddableType)
        $blockWithNestedFields = null;
        foreach ($blocks as $block) {
            foreach ($block['fields'] as $field) {
                if (isset($field['fields']) && is_array($field['fields']) && !empty($field['fields'])) {
                    $blockWithNestedFields = $block;
                    $nestedField = $field;
                    break 2;
                }
            }
        }

        if ($blockWithNestedFields !== null) {
            $this->assertIsArray($nestedField['fields']);
            $this->assertNotEmpty($nestedField['fields']);

            // Check structure of nested field
            $firstNestedField = $nestedField['fields'][0];
            $this->assertArrayHasKey('name', $firstNestedField);
            $this->assertArrayHasKey('type', $firstNestedField);
        } else {
            $this->markTestSkipped('No blocks with nested fields found for testing');
        }
    }
}
