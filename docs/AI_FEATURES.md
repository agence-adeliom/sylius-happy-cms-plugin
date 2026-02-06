# AI Content Generation

This document explains how to configure and use the AI-powered content generation feature in the Happy CMS Page Builder.

## Overview

The AI content generation feature allows you to automatically generate content blocks based on a text description. The AI analyzes your requirements and creates appropriate blocks with relevant content.

## Requirements

- PHP 8.1 or higher
- Symfony AI Bundle 0.2 or higher
- An AI provider configured (OpenAI, Anthropic Claude, etc.)

## Installation

### 1. Install Symfony AI Bundle

```bash
composer require symfony/ai-bundle
composer require symfony/ai-chat
composer require symfony/ai-open-ai-platform # or composer require symfony/ai-anthropic-platform
```

### 2. Configure AI Provider

The Happy CMS plugin uses the default AI chat service configured in your Symfony application. You can configure any supported provider:

#### Example: OpenAI Configuration

```yaml
# config/packages/ai.yaml
ai:
    platform:
      openai:
         api_key: '%env(OPENAI_API_KEY)%'
    agent:
      default:
         model: 'gpt-4o-mini' # or gpt-4, gpt-3.5-turbo
```

```bash
bin/console secret:set OPENAI_API_KEY your_api_key_here
```

#### Example: Anthropic Claude Configuration

```yaml
# config/packages/ai.yaml
ai:
    platform:
        anthropic:
            api_key: '%env(ANTHROPIC_API_KEY)%'
    agent:
        default:
            model: 'claude-3-5-sonnet-20241022' 
```

```bash
bin/console secret:set ANTHROPIC_API_KEY your_api_key_here
```

### 3. Customize AI System Prompt (Optional)

The Happy CMS plugin allows you to customize the system prompt used for AI content generation. This is useful if you want to:
- Adjust the AI behavior to match your specific requirements
- Add additional instructions or constraints
- Modify the output format or style

#### Configuration

Add the `ai_generate_content_prompt` option to your bundle configuration:

```yaml
# config/packages/sylius_happy_cms.yaml
sylius_happy_cms:
    page_builder:
        ai_generate_content_prompt: |
            You are a specialized content generator for our e-commerce platform.
            Your task is to generate content blocks based on user requirements.

            Available blocks that you can use:
            {blocks_schema}

            Important instructions:
            1. Focus on e-commerce and product-related content
            2. Use professional and persuasive tone
            3. Generate content in a format that drives conversions
            4. Return ONLY a valid JSON array containing the generated blocks
            5. Each block must have this exact structure:
               {
                 "block_type": "Full\\Namespace\\BlockType",
                 "position": 0,
                 "block_published": true,
                 "data": {
                   "field_name": "field_value",
                   ...
                 }
               }

            Your response must be a valid JSON array starting with [ and ending with ].
```

**Important placeholders:**
- `{blocks_schema}` - Will be automatically replaced with the JSON schema of available blocks

**Notes:**
- The custom prompt should instruct the AI to return valid JSON in the same format as the default prompt
- You can add domain-specific instructions, tone guidance, or content restrictions
- The `{blocks_schema}` placeholder is required and will be replaced with the actual block definitions
- If not configured, the bundle will use a default system prompt optimized for general content generation

**Example use cases for custom prompts:**
- E-commerce focus: Emphasize product features, benefits, and calls-to-action
- B2B content: Professional tone with technical details and ROI focus
- Marketing campaigns: Persuasive language with urgency and emotional appeal
- Educational content: Clear explanations with structured information
- Multi-language projects: Add specific language or localization instructions

### 4. Set Environment Variables

Add your API key to `.env.local`:

```env
# For OpenAI
OPENAI_API_KEY=your_openai_api_key_here

# For Anthropic Claude
ANTHROPIC_API_KEY=your_anthropic_api_key_here
```

Or use Symfony secrets management for better security:

```bash
bin/console secret:set OPENAI_API_KEY your_openai_api_key_here
```

### 5. Configure blocks to use AI content generation

Example configuration for a content block type:
Only blocks annotated with `#[AIGeneratable]` will be available for AI content generation.
Priority can be set to influence the order in which blocks are suggested.

```php
#[AIGeneratable(
    description: 'An accordion block with expandable/collapsible items for FAQs and structured content',
    useCases: ['FAQ sections', 'Q&A pages', 'help documentation', 'feature lists', 'product specifications'],
    priority: 1,
)]
class AccordionBlockType extends AbstractBlock 
{
    // Block implementation...
}
```
