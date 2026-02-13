<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\AI;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AIGeneratable;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to serialize CMS blocks into a structured JSON format for AI consumption
 */
class BlockSchemaSerializer
{
    public function __construct(
        private BlockCollection $blockCollection,
        private FormFactoryInterface $formFactory,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Serialize all available blocks into a structured array
     *
     * @param bool $onlyAIGeneratable If true, only include blocks with #[AIGeneratable] attribute
     *
     * @return array{
     *     blocks: array<int, array<string, mixed>>,
     *     total_count: int
     * }
     */
    public function serializeBlocks(bool $onlyAIGeneratable = true): array
    {
        $blocks = $this->blockCollection->getBlocks();
        $serializedBlocks = [];

        /** @var class-string $blockClass */
        foreach ($blocks as $blockClass => $block) {
            // Filter by AIGeneratable attribute if requested
            if ($onlyAIGeneratable && !$this->isAIGeneratable($blockClass)) {
                continue;
            }

            $serializedBlocks[] = $this->serializeBlock($block, $blockClass);
        }

        return [
            'blocks' => $serializedBlocks,
            'total_count' => count($serializedBlocks),
        ];
    }

    /**
     * Check if a block class has the AIGeneratable attribute
     * @param class-string $blockClass
     */
    private function isAIGeneratable(string $blockClass): bool
    {
        try {
            $reflection = new \ReflectionClass($blockClass);
            $attributes = $reflection->getAttributes(AIGeneratable::class);

            return count($attributes) > 0;
        } catch (\ReflectionException $e) {
            return false;
        }
    }

    /**
     * Serialize a single block into a structured array
     * @param class-string $blockClass
     * @return array<string, mixed>
     */
    private function serializeBlock(BlockTypeInterface $block, string $blockClass): array
    {
        $form = $this->createBlockForm($block);

        $blockData = [
            'namespace' => $blockClass,
            'name' => $this->translateLabel($block->getName()),
            'fields' => $this->extractFormFields($form),
        ];

        // Add AIGeneratable metadata if present
        $aiMetadata = $this->getAIGeneratableMetadata($blockClass);
        if ($aiMetadata !== null) {
            $blockData['ai_metadata'] = $aiMetadata;
        }

        return $blockData;
    }

    /**
     * Extract metadata from AIGeneratable attribute
     * @param class-string $blockClass
     * @return array<string, mixed>|null
     */
    private function getAIGeneratableMetadata(string $blockClass): ?array
    {
        try {
            $reflection = new \ReflectionClass($blockClass);
            $attributes = $reflection->getAttributes(AIGeneratable::class);

            if (empty($attributes)) {
                return null;
            }

            /** @var AIGeneratable $aiGeneratable */
            $aiGeneratable = $attributes[0]->newInstance();

            $metadata = [
                'priority' => $aiGeneratable->priority,
            ];

            if ($aiGeneratable->description !== null) {
                $metadata['description'] = $aiGeneratable->description;
            }

            if (!empty($aiGeneratable->useCases)) {
                $metadata['use_cases'] = $aiGeneratable->useCases;
            }

            return $metadata;
        } catch (\ReflectionException $e) {
            return null;
        }
    }

    /**
     * Create a form for a block to introspect its fields
     */
    private function createBlockForm(BlockTypeInterface $block): FormInterface
    {
        return $this->formFactory->create($block::class);
    }

    /**
     * Extract fields from a form recursively
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractFormFields(FormInterface $form): array
    {
        $fields = [];

        foreach ($form as $child) {
            $config = $child->getConfig();
            $fieldName = $child->getName();

            // Skip internal fields
            if (in_array($fieldName, ['block_type', 'block_published', 'position'])) {
                continue;
            }

            $fieldType = get_class($config->getType()->getInnerType());

            $label = $config->getOption('label', $fieldName);

            if (is_string($label)) {
                $translatedLabel = $this->translateLabel($label);
            }

            $fieldData = [
                'name' => $fieldName,
                'type' => $this->simplifyFieldType($fieldType),
                'required' => $config->getRequired(),
                'label' => $translatedLabel ?? 'No label',
            ];

            // Handle nested forms (like ButtonEmbeddableType)
            if ($child->count() > 0) {
                $fieldData['fields'] = $this->extractFormFields($child);
            }

            // Extract additional useful options
            if ($config->hasOption('help')) {
                $help = $config->getOption('help');
                if (is_string($help)) {
                    $fieldData['help'] = $this->translateLabel($help);
                }
            }

            if ($config->hasOption('choices')) {
                $fieldData['choices'] = $config->getOption('choices');
            }

            $fields[] = $fieldData;
        }

        return $fields;
    }

    /**
     * Simplify field type class names to more readable types
     * Uses inheritance and interfaces to detect type instead of just class name
     * @param class-string $fullClassName
     */
    private function simplifyFieldType(string $fullClassName): string
    {
        // Check by parent class / interface first (more reliable)
        if (is_a($fullClassName, TextType::class, true)) {
            return 'text';
        }
        if (is_a($fullClassName, TextareaType::class, true)) {
            return 'textarea';
        }
        if (is_a($fullClassName, EmailType::class, true)) {
            return 'email';
        }
        if (is_a($fullClassName, IntegerType::class, true)) {
            return 'integer';
        }
        if (is_a($fullClassName, NumberType::class, true)) {
            return 'number';
        }
        if (is_a($fullClassName, MoneyType::class, true)) {
            return 'money';
        }
        if (is_a($fullClassName, CheckboxType::class, true)) {
            return 'checkbox';
        }
        if (is_a($fullClassName, ChoiceType::class, true)) {
            return 'choice';
        }
        if (is_a($fullClassName, DateType::class, true)) {
            return 'date';
        }
        if (is_a($fullClassName, DateTimeType::class, true)) {
            return 'datetime';
        }
        if (is_a($fullClassName, FileType::class, true)) {
            return 'file';
        }
        if (is_a($fullClassName, HiddenType::class, true)) {
            return 'hidden';
        }
        if (is_a($fullClassName, UrlType::class, true)) {
            return 'url';
        }
        if (is_a($fullClassName, CollectionType::class, true)) {
            return 'collection';
        }

        // Custom plugin types - check by class name as fallback for non-inherited types
        $shortName = substr($fullClassName, strrpos($fullClassName, '\\') + 1);
        $customTypeMap = [
            'TinymceBridgeType' => 'wysiwyg',
            'ButtonEmbeddableType' => 'button',
        ];

        if (isset($customTypeMap[$shortName])) {
            return $customTypeMap[$shortName];
        }

        // Return the short class name as fallback
        return $shortName;
    }

    /**
     * Get serialized blocks as JSON string
     *
     * @param bool $onlyAIGeneratable If true, only include blocks with #[AIGeneratable] attribute
     */
    public function toJson(bool $onlyAIGeneratable = true): string
    {
        return json_encode($this->serializeBlocks($onlyAIGeneratable), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE)
            ?: '';
    }

    /**
     * Translate a label if it's a translation key, otherwise return as-is
     */
    private function translateLabel(?string $label): ?string
    {
        // Try to translate the label using the default locale
        // If the translation returns the same string, it might be a translation key
        // that doesn't exist, so we return the original label
        return null !== $label ? $this->translator->trans($label) : $label;
    }
}
