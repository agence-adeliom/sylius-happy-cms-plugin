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

### 3. Set Environment Variables

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

### 4. Configure blocks to use AI content generation

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
