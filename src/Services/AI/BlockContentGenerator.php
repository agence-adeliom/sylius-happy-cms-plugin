<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\AI;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

/**
 * Service to generate block content using AI agents
 */
final readonly class BlockContentGenerator
{
    public function __construct(
        private BlockSchemaSerializer $blockSchemaSerializer,
        private ?AgentInterface $agent = null,
    ) {
    }

    /**
     * Generate blocks based on user prompt
     *
     *
     * @throws \RuntimeException If AI service is not available
     */
    public function generateBlocks(string $userPrompt, int $blockCount): GeneratedBlocksOutput
    {
        if (null === $this->agent) {
            throw new \RuntimeException('AI service is not configured. Please install and configure Symfony AI Bundle.');
        }

        // Get the schema of available AI-generatable blocks
        $blockSchema = $this->blockSchemaSerializer->serializeBlocks(onlyAIGeneratable: true);

        // Build the system prompt with block schema
        $systemPrompt = $this->buildSystemPrompt($blockSchema);

        // Build the user prompt
        $userMessage = $this->buildUserPrompt($userPrompt, $blockCount);

        // Call the AI agent
        $messages = new MessageBag(
            Message::forSystem($systemPrompt),
            Message::ofUser($userMessage),
        );

        $result = $this->agent->call($messages);

        if ($result->getContent() === null) {
            throw new \RuntimeException('AI agent returned an empty response.');
        }

        // Parse the response
        return $this->parseResponse($result->getContent());
    }

    /**
     * Build the system prompt with available blocks schema
     *
     * @param array<string, mixed> $blockSchema
     */
    private function buildSystemPrompt(array $blockSchema): string
    {
        $blocksJson = json_encode($blockSchema, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are a content generator for a Sylius CMS page builder. Your task is to generate content blocks based on user requirements.

Available blocks that you can use:
{$blocksJson}

Important instructions:
1. Analyze the user's request and select the most appropriate blocks from the available blocks above.
2. Generate content that matches the user's description and requirements.
3. Return ONLY a valid JSON array containing the generated blocks.
4. Each block must have this exact structure:
   {
     "block_type": "Full\\Namespace\\BlockType",
     "position": 0,
     "block_published": true,
     "data": {
       "field_name": "field_value",
       ...
     }
   }
5. The "data" object must contain field names and values that match the block's field schema.
6. For WYSIWYG fields, generate rich HTML content with proper formatting.
7. For button/CTA fields with nested structure, use the proper nested format:
   "cta_one": {
     "label": "Button Text",
     "link": "https://example.com"
   }
8. Ensure positions start at 0 and increment sequentially.
9. Set block_published to true for all generated blocks.
10. Do not include any markdown code fences, explanations, or text outside the JSON array.
11. The response must be ONLY valid JSON that can be parsed directly.
12. Do not treat media or image or pictures fields as URLs; instead, return empty values for them.
13. Vary the block types and content to create a diverse set of blocks.

Your response must be a valid JSON array starting with [ and ending with ].
PROMPT;
    }

    /**
     * Build the user prompt
     */
    private function buildUserPrompt(string $userPrompt, int $blockCount): string
    {
        return <<<PROMPT
Generate exactly {$blockCount} content blocks for the following requirement:

{$userPrompt}

Remember to return ONLY a valid JSON array of blocks. No markdown, no explanations, just the JSON array.
PROMPT;
    }

    /**
     * Parse the AI response and convert it to GeneratedBlocksOutput
     */
    private function parseResponse(string $content): GeneratedBlocksOutput
    {
        // Clean up potential markdown code fences
        $content = preg_replace('/^```json\s*/m', '', $content);
        $content = preg_replace('/^```\s*/m', '', $content);
        $content = trim($content);

        // Parse JSON
        try {
            $blocks = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Failed to parse AI response as JSON: ' . $e->getMessage() . "\nContent: " . $content);
        }

        if (!is_array($blocks)) {
            throw new \RuntimeException('AI response is not an array');
        }

        // Validate and normalize blocks
        $normalizedBlocks = [];
        foreach ($blocks as $index => $block) {
            if (!isset($block['block_type'], $block['data'])) {
                throw new \RuntimeException("Block at index {$index} is missing required fields (block_type, data)");
            }

            $normalizedBlocks[] = [
                'block_type' => $block['block_type'],
                'position' => $block['position'] ?? $index,
                'block_published' => $block['block_published'] ?? true,
                'data' => $block['data'],
            ];
        }

        return new GeneratedBlocksOutput($normalizedBlocks);
    }
}
