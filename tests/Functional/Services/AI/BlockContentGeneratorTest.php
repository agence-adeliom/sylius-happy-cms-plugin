<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Tests\Functional\Services\AI;

use Adeliom\SyliusHappyCMSPlugin\Services\AI\BlockContentGenerator;
use Adeliom\SyliusHappyCMSPlugin\Services\AI\BlockSchemaSerializer;
use Adeliom\SyliusHappyCMSPlugin\Services\AI\GeneratedBlocksOutput;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\Result;

final class BlockContentGeneratorTest extends TestCase
{
    public function testGenerateBlocksThrowsExceptionWhenChatNotConfigured(): void
    {
        $blockSchemaSerializer = $this->createMock(BlockSchemaSerializer::class);
        $generator = new BlockContentGenerator($blockSchemaSerializer, null, null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AI service is not configured');

        $generator->generateBlocks('Test prompt', 2);
    }

    public function testCustomSystemPromptIsUsed(): void
    {
        $blockSchemaSerializer = $this->createMock(BlockSchemaSerializer::class);
        $blockSchemaSerializer->method('serializeBlocks')->willReturn([
            'blocks' => [
                [
                    'namespace' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\TextCtaBlockType',
                    'name' => 'Text CTA',
                    'fields' => [
                        ['name' => 'title', 'type' => 'text', 'required' => true],
                    ],
                ],
            ],
            'total_count' => 1,
        ]);

        // Define a custom system prompt with the placeholder
        $customPrompt = 'Custom AI instructions. Available blocks: {blocks_schema}';

        // Mock AI agent to verify the custom prompt is used
        $agent = $this->createMock(\Symfony\AI\Agent\AgentInterface::class);
        $agent->expects($this->once())
            ->method('call')
            ->with($this->callback(function ($messages) use ($customPrompt) {
                // Verify that the system message contains our custom prompt structure
                $systemMessage = $messages->getMessages()[0];
                $content = $systemMessage->getContent();
                // Check that custom prompt text is present
                return str_contains($content, 'Custom AI instructions');
            }))
            ->willReturn($this->createMockResult('[{"block_type":"Test","position":0,"block_published":true,"data":{}}]'));

        $generator = new BlockContentGenerator($blockSchemaSerializer, $agent, $customPrompt);
        $generator->generateBlocks('Test', 1);
    }

    public function testDefaultSystemPromptIsUsedWhenNoCustomPrompt(): void
    {
        $blockSchemaSerializer = $this->createMock(BlockSchemaSerializer::class);
        $blockSchemaSerializer->method('serializeBlocks')->willReturn([
            'blocks' => [],
            'total_count' => 0,
        ]);

        // Mock AI agent to verify the default prompt is used
        $agent = $this->createMock(\Symfony\AI\Agent\AgentInterface::class);
        $agent->expects($this->once())
            ->method('call')
            ->with($this->callback(function ($messages) {
                // Verify that the system message contains default prompt text
                $systemMessage = $messages->getMessages()[0];
                $content = $systemMessage->getContent();
                // Check for default prompt characteristics
                return str_contains($content, 'You are a content generator for a Sylius CMS page builder');
            }))
            ->willReturn($this->createMockResult('[{"block_type":"Test","position":0,"block_published":true,"data":{}}]'));

        $generator = new BlockContentGenerator($blockSchemaSerializer, $agent, null);
        $generator->generateBlocks('Test', 1);
    }

    private function createMockResult(string $content): Result
    {
        $result = $this->createMock(Result::class);
        $result->method('getContent')->willReturn($content);
        return $result;
    }

    public function testGenerateBlocksReturnsGeneratedBlocksOutput(): void
    {
        // Mock BlockSchemaSerializer
        $blockSchemaSerializer = $this->createMock(BlockSchemaSerializer::class);
        $blockSchemaSerializer->method('serializeBlocks')->willReturn([
            'blocks' => [
                [
                    'namespace' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\TextCtaBlockType',
                    'name' => 'Text CTA',
                    'fields' => [
                        ['name' => 'title', 'type' => 'text', 'required' => true],
                        ['name' => 'wysiwyg', 'type' => 'wysiwyg', 'required' => true],
                    ],
                ],
            ],
            'total_count' => 1,
        ]);

        // Mock AI response
        $aiResponse = json_encode([
            [
                'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\TextCtaBlockType',
                'position' => 0,
                'block_published' => true,
                'data' => [
                    'title' => 'Test Title',
                    'wysiwyg' => '<p>Test content</p>',
                ],
            ],
            [
                'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\TextCtaBlockType',
                'position' => 1,
                'block_published' => true,
                'data' => [
                    'title' => 'Another Title',
                    'wysiwyg' => '<p>More content</p>',
                ],
            ],
        ]);

        $assistantMessage = new AssistantMessage($aiResponse);
        $chatResponse = new ChatResponse($assistantMessage);

        // Mock ChatInterface
        $chat = $this->createMock(ChatInterface::class);
        $chat->method('generate')->willReturn($chatResponse);

        // Create generator
        $generator = new BlockContentGenerator($blockSchemaSerializer, $chat, null);

        // Test
        $result = $generator->generateBlocks('Generate content for e-commerce page', 2);

        $this->assertInstanceOf(GeneratedBlocksOutput::class, $result);
        $this->assertSame(2, $result->getCount());

        $blocks = $result->getBlocks();
        $this->assertCount(2, $blocks);
        $this->assertSame('Adeliom\\SyliusHappyCMSPlugin\\Block\\TextCtaBlockType', $blocks[0]['block_type']);
        $this->assertSame('Test Title', $blocks[0]['data']['title']);
    }

    public function testGenerateBlocksHandlesMarkdownCodeFences(): void
    {
        $blockSchemaSerializer = $this->createMock(BlockSchemaSerializer::class);
        $blockSchemaSerializer->method('serializeBlocks')->willReturn([
            'blocks' => [],
            'total_count' => 0,
        ]);

        // Mock AI response with markdown code fences
        $aiResponse = <<<JSON
```json
[
    {
        "block_type": "Test\\Block",
        "position": 0,
        "block_published": true,
        "data": {
            "title": "Test"
        }
    }
]
```
JSON;

        $assistantMessage = new AssistantMessage($aiResponse);
        $chatResponse = new ChatResponse($assistantMessage);

        $chat = $this->createMock(ChatInterface::class);
        $chat->method('generate')->willReturn($chatResponse);

        $generator = new BlockContentGenerator($blockSchemaSerializer, $chat, null);
        $result = $generator->generateBlocks('Test prompt', 1);

        $this->assertSame(1, $result->getCount());
        $blocks = $result->getBlocks();
        $this->assertSame('Test\\Block', $blocks[0]['block_type']);
    }

    public function testGeneratedBlocksOutputGetters(): void
    {
        $blocks = [
            [
                'block_type' => 'Test\\Block',
                'position' => 0,
                'block_published' => true,
                'data' => ['title' => 'Test'],
            ],
        ];

        $output = new GeneratedBlocksOutput($blocks);

        $this->assertSame($blocks, $output->getBlocks());
        $this->assertSame(1, $output->getCount());
    }
}
