<div align="center">

# AI Features

</div>

## Table of Contents

- [Overview](#overview)
- [Use AI Guides](#use-ai-guides)
- [Content Generation](#content-generation)
- [Content Translation](#content-translation-coming-soon)
- [Best Practices](#best-practices)

## Overview

Happy CMS provides AI-powered features to enhance content creation and management:

1. **AI Guides** - Interactive assistance for using CMS features
2. **Content Generation** - Automatic block creation based on descriptions
3. **Content Translation** - Multi-language content translation (coming soon)

These features leverage Symfony AI Bundle and support multiple AI providers (OpenAI, Anthropic Claude, etc.).

---

## Use AI Guides

AI Guides provide contextual assistance when working with CMS features. They help users understand capabilities and make the most of the page builder.

### How It Works

AI Guides analyze the current context and provide:
- Step-by-step instructions
- Best practice recommendations
- Examples and use cases
- Troubleshooting assistance

### Enabling AI Guides

AI Guides are automatically available when an AI provider is configured (see [Content Generation Configuration](#installation) below).

### Using AI Guides

In the page builder interface:
1. Look for the AI assistant icon
2. Ask questions about CMS features
3. Get contextual help based on your current page
4. Follow AI suggestions to optimize your content

---

## Content Generation

The AI content generation feature allows you to automatically create content blocks based on text descriptions. The AI analyzes your requirements and generates appropriate blocks with relevant content.

### Requirements

- PHP 8.1 or higher
- Symfony AI Bundle 0.2 or higher
- An AI provider (OpenAI, Anthropic Claude, etc.)

### Installation

#### Step 1: Install Symfony AI Bundle

```bash
composer require symfony/ai-bundle
composer require symfony/ai-chat
# Choose your AI provider:
composer require symfony/ai-open-ai-platform
# or
composer require symfony/ai-anthropic-platform
```

#### Step 2: Configure AI Provider

The Happy CMS plugin uses the default AI chat service configured in your Symfony application.

##### Option A: OpenAI Configuration

**Configuration**: `config/packages/ai.yaml`

```yaml
ai:
    platform:
        openai:
            api_key: '%env(OPENAI_API_KEY)%'
    agent:
        default:
            model: 'gpt-4o-mini'  # or gpt-4, gpt-3.5-turbo
```

**Environment variable**: `.env.local`

```env
OPENAI_API_KEY=your_openai_api_key_here
```

**Or use secrets** (recommended for production):

```bash
bin/console secret:set OPENAI_API_KEY your_api_key_here
```

##### Option B: Anthropic Claude Configuration

**Configuration**: `config/packages/ai.yaml`

```yaml
ai:
    platform:
        anthropic:
            api_key: '%env(ANTHROPIC_API_KEY)%'
    agent:
        default:
            model: 'claude-3-5-sonnet-20241022'
```

**Environment variable**: `.env.local`

```env
ANTHROPIC_API_KEY=your_anthropic_api_key_here
```

**Or use secrets** (recommended for production):

```bash
bin/console secret:set ANTHROPIC_API_KEY your_api_key_here
```

#### Step 3: Mark Blocks as AI-Generatable

Only blocks annotated with `#[AIGeneratable]` are available for AI content generation.

**Example block**:

```php
<?php

declare(strict_types=1);

namespace App\Block;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AIGeneratable;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;

#[AIGeneratable(
    description: 'An accordion block with expandable/collapsible items for FAQs and structured content',
    useCases: ['FAQ sections', 'Q&A pages', 'help documentation', 'feature lists', 'product specifications'],
    priority: 120,
)]
class AccordionBlockType extends AbstractBlock
{
    // Block implementation...
}
```

**Attribute parameters**:
- `description`: Clear description of block purpose
- `useCases`: Array of common use cases
- `priority`: Display order (higher priority = suggested first)

#### Step 4: Customize System Prompt (Optional)

Customize the AI behavior by configuring the system prompt.

**Configuration**: `config/packages/sylius_happy_cms.yaml`

```yaml
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
            3. Generate content that drives conversions
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

**Important placeholders**:
- `{blocks_schema}` - Automatically replaced with JSON schema of available blocks (required)

**Customization use cases**:
- **E-commerce**: Emphasize product features, benefits, and CTAs
- **B2B**: Professional tone with technical details and ROI focus
- **Marketing**: Persuasive language with urgency and emotional appeal
- **Education**: Clear explanations with structured information
- **Multi-language**: Add specific language or localization instructions

**Notes**:
- Custom prompt must instruct AI to return valid JSON in the same format
- The `{blocks_schema}` placeholder is required
- If not configured, uses default prompt optimized for general content generation

### Using Content Generation

In the page builder interface:

1. **Click the AI generation button**
2. **Describe your content** (e.g., "Create a FAQ section about shipping policies")
3. **Review generated blocks** - AI suggests appropriate block types with content
4. **Accept or modify** - Use as-is or adjust generated content
5. **Publish** - Add blocks to your page

**Example prompts**:
- "Create a hero section for a new product launch"
- "Generate a feature comparison table"
- "Build an FAQ section about returns and refunds"
- "Create testimonials for our consulting services"
- "Generate a pricing section with 3 tiers"

### Supported Block Types

All blocks marked with `#[AIGeneratable]` are available. Default blocks include:

- **WysiwygBlockType** - Rich text content
- **AccordionBlockType** - FAQ and collapsible content
- **CtaBlockType** - Call-to-action buttons
- **TextCtaBlockType** - Text with CTA
- **KeyFeaturesBlockType** - Feature showcases
- **GalleryBlockType** - Image galleries
- **TextImageCtaBlockType** - Combined text, image, and CTA

### JSON Output Format

AI generates blocks in this format:

```json
[
  {
    "block_type": "Adeliom\\SyliusHappyCMSPlugin\\Block\\AccordionBlockType",
    "position": 0,
    "block_published": true,
    "data": {
      "title": "Frequently Asked Questions",
      "wysiwyg": "<p>Find answers to common questions below.</p>",
      "items": [
        {
          "title": "What is your return policy?",
          "content": "You can return items within 30 days..."
        }
      ]
    }
  }
]
```

### Troubleshooting

#### AI Generation Not Working

**Check**:
1. AI provider configured in `config/packages/ai.yaml`
2. API key set in environment or secrets
3. Symfony AI Bundle installed
4. At least one block has `#[AIGeneratable]` attribute
5. Cache cleared: `php bin/console cache:clear`

**Test configuration**:
```bash
# Verify AI service is available
php bin/console debug:container | grep ai.chat
```

#### Invalid Response Format

**If AI returns invalid JSON**:
1. Check system prompt includes JSON format instructions
2. Verify `{blocks_schema}` placeholder is present
3. Try a different AI model (e.g., GPT-4 instead of GPT-3.5)
4. Simplify prompt to reduce complexity

#### Rate Limiting

**If hitting API limits**:
1. Upgrade AI provider plan
2. Implement caching for similar requests
3. Use more efficient models (e.g., gpt-3.5-turbo)
4. Add rate limiting to prevent abuse

---

## Content Translation (Coming Soon)

AI-powered content translation will allow automatic translation of CMS content across multiple languages.

### Planned Features

- **Automatic translation** of content blocks
- **Context-aware translations** maintaining tone and style
- **Batch translation** for multiple pages
- **Translation memory** for consistency
- **Custom translation prompts** per language pair

### Configuration (Upcoming)

```yaml
# Future configuration example
sylius_happy_cms:
    page_builder:
        ai_translate_content_prompt: |
            Translate the following content to {target_language}.
            Maintain the original tone, style, and formatting.
            Content: {content}
```

### Stay Updated

Translation features are in development. Check the [changelog](../CHANGELOG.md) for updates.

---

## Best Practices

### For Content Generation

1. **Be specific in prompts**
   - ❌ "Create content about products"
   - ✅ "Create a product comparison table highlighting 3 key features for software subscriptions"

2. **Review generated content**
   - Always review AI-generated content before publishing
   - Adjust tone and style to match brand voice
   - Verify factual accuracy

3. **Use appropriate blocks**
   - Mark blocks with relevant use cases in `#[AIGeneratable]`
   - Set priority to guide AI toward best block choices
   - Provide clear block descriptions

4. **Customize system prompts**
   - Tailor prompts to your domain and audience
   - Include brand guidelines in system prompt
   - Add specific formatting requirements

5. **Manage API costs**
   - Use efficient models (gpt-3.5-turbo, claude-haiku)
   - Cache common requests
   - Set up rate limiting

### For Custom Blocks

When creating AI-generatable blocks:

```php
#[AIGeneratable(
    description: 'Clear, concise description of block purpose',
    useCases: [
        'Specific use case 1',
        'Specific use case 2',
        'Specific use case 3',
    ],
    priority: 100, // Higher number = higher priority
)]
class CustomBlock extends AbstractBlock
{
    // Ensure buildBlock() has clear field names
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Block Title',  // Clear labels help AI
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Main Content',
            ]);
    }
}
```

### Security Considerations

1. **Protect API keys**
   - Use Symfony secrets management
   - Never commit keys to version control
   - Rotate keys regularly

2. **Validate AI output**
   - Sanitize generated content before rendering
   - Check for inappropriate content
   - Verify data structure

3. **Rate limiting**
   - Implement request throttling
   - Monitor API usage
   - Set up alerts for unusual activity

4. **User permissions**
   - Restrict AI features to authorized users
   - Log AI generation requests
   - Implement content approval workflows

### Performance Tips

1. **Choose appropriate models**
   - Development: Use faster, cheaper models (gpt-3.5-turbo)
   - Production: Balance quality and cost (gpt-4o-mini)

2. **Optimize prompts**
   - Keep system prompts concise
   - Avoid unnecessary instructions
   - Use clear, direct language

3. **Cache responses**
   - Cache similar prompts
   - Store generated blocks for reuse
   - Implement smart invalidation

4. **Monitor usage**
   - Track API calls and costs
   - Set up budget alerts
   - Analyze generation patterns
