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

## Usage

### In the Page Builder

1. Open the Page Builder for any content page
2. Click the **"Generate content with AI"** button (green button next to "Browse blocks")
3. In the AI Content Generator panel:
   - Enter a description of the content you want to generate
   - Specify the number of blocks (1-10)
   - Click "Generate"
4. The AI will create the blocks and add them to your page automatically

### Example Prompts

**E-commerce Service Page:**
```
I want to generate content for a service page that explains our e-commerce development services.
Include a headline, description, key features, and a call-to-action.
```

**Product Landing Page:**
```
Create content for a landing page promoting our premium product line.
Focus on benefits, features, testimonials, and pricing.
```

**About Us Page:**
```
Generate content for our company's about page.
Include company history, mission statement, team introduction, and values.
```

## How It Works

1. **Block Schema**: The system provides the AI with information about available blocks and their fields
2. **AI Analysis**: The AI analyzes your prompt and selects the most appropriate blocks
3. **Content Generation**: The AI generates realistic content for each selected block
4. **Validation**: The response is validated and normalized
5. **Block Creation**: New blocks are created and added to your page

## AI-Generatable Blocks

Only blocks marked with the `#[AIGeneratable]` attribute can be used by the AI. Current AI-generatable blocks include:

- **WysiwygBlockType** (Priority: 200) - Rich text content
- **TextCtaBlockType** (Priority: 150) - Text with call-to-action buttons
- **CtaBlockType** (Priority: 140) - Call-to-action block
- **TextImageCtaBlockType** (Priority: 135) - Text with image and CTA
- **KeyFeaturesBlockType** (Priority: 130) - Key features list
- **AccordionBlockType** (Priority: 120) - Accordion/FAQ
- **GalleryBlockType** (Priority: 110) - Image gallery

Higher priority blocks are more likely to be selected by the AI when appropriate.

## Advanced Configuration

### Custom AI Agent

If you need to use a specific AI agent instead of the default one, you can configure it in your services:

```yaml
# config/services.yaml
services:
    Adeliom\SyliusHappyCMSPlugin\Service\AI\BlockContentGenerator:
        arguments:
            $chat: '@your_custom_chat_service'
```

### Viewing Available Blocks

To see all AI-generatable blocks and their schemas:

```bash
php bin/console happy-cms:ai:show-block-schema
```

To export the schema to a file:

```bash
php bin/console happy-cms:ai:show-block-schema --output=blocks-schema.json
```

## Troubleshooting

### AI Bundle Not Detected

If you see the message "AI Bundle Not Configured":

1. Verify that `symfony/ai-bundle` is installed: `composer show symfony/ai-bundle`
2. Check that the bundle is registered in `config/bundles.php`
3. Ensure you have configured at least one AI provider

### Generation Errors

If content generation fails:

1. Check your API key is valid and has sufficient credits
2. Verify your internet connection
3. Review your prompt - make it more specific and detailed
4. Try reducing the number of blocks to generate
5. Check Symfony logs for detailed error messages: `var/log/dev.log`

### No Blocks Generated

If the AI returns no blocks:

1. Make your prompt more specific
2. Explicitly mention the type of content you want
3. Increase the number of blocks to generate
4. Ensure AI-generatable blocks are available (check with `happy-cms:ai:show-block-schema`)

## Best Practices

### Writing Effective Prompts

1. **Be Specific**: Include details about the purpose, audience, and tone
2. **Mention Structure**: Specify if you want specific sections or elements
3. **Provide Context**: Explain what the page/section is for
4. **Set Expectations**: Mention desired content types (features, benefits, etc.)

### Examples of Good Prompts

✅ **Good:**
```
Create content for a landing page promoting our SaaS project management tool.
Target audience: small business owners and team leaders.
Include: hero section with benefits, key features list, pricing comparison, and strong call-to-action.
Tone: professional but friendly.
```

❌ **Too Vague:**
```
Make some content about our product.
```

### Performance Tips

- Generate 3-5 blocks at a time for optimal results
- More specific prompts lead to better content quality
- Review and edit generated content before publishing
- Use the draft/preview system to review before making content live

## API Costs

Be aware that each AI generation request consumes API credits from your provider:

- **OpenAI GPT-4**: Higher cost, better quality
- **OpenAI GPT-3.5**: Lower cost, good quality
- **Anthropic Claude**: Varies by model tier

Monitor your API usage through your provider's dashboard.

## Security

- Never commit API keys to version control
- Use environment variables for sensitive configuration
- Restrict API key permissions to only what's needed
- Monitor API usage for unexpected activity
- Review generated content before publishing

## Further Reading

- [Symfony AI Bundle Documentation](https://symfony.com/doc/current/ai.html)
- [OpenAI API Documentation](https://platform.openai.com/docs)
- [Anthropic Claude API Documentation](https://docs.anthropic.com/)
