<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Enum\Config;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CheckboxField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CodeEditorField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\FormTypeField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\Helper\Enum;
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\MediaField;
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\TinyMCEField;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * ConfigTypeEnum enum.
 *
 * @method static ConfigTypeEnum CODE()
 * @method static ConfigTypeEnum EMAIL()
 * @method static ConfigTypeEnum NUMBER()
 * @method static ConfigTypeEnum JSON()
 * @method static ConfigTypeEnum TEXT()
 * @method static ConfigTypeEnum TEXTAREA()
 * @method static ConfigTypeEnum WYSIWYG()
 * @method static ConfigTypeEnum BOOLEAN()
 * @method static ConfigTypeEnum IMAGE()
 * @method static ConfigTypeEnum FILE()
 * @method static ConfigTypeEnum COLOR()
 * @method static ConfigTypeEnum DATE()
 * @method static ConfigTypeEnum TIME()
 * @method static ConfigTypeEnum DATETIME()
 */
final class ConfigTypeEnum extends Enum
{
    public const CODE = 'code';

    public const EMAIL = 'email';

    public const NUMBER = 'number';

    public const JSON = 'json';

    public const TEXT = 'text';

    public const TEXTAREA = 'textarea';

    public const WYSIWYG = 'wysiwyg';

    public const BOOLEAN = 'boolean';

    public const IMAGE = 'image';

    public const DATE = 'date';

    public const TIME = 'time';

    public const DATETIME = 'datetime';

    /**
     * @param string $property property of the translation the field is bound to ("value" by default,
     *                         `channel_<code>` for a per-channel value, which is never required)
     */
    public static function getAdminField(string $typeKey, string $property = 'value', TranslatableInterface|string|null $label = null): FieldInterface
    {
        $field = Field::new($property);

        if (
            $typeKey === self::CODE ||
            $typeKey === self::JSON
        ) {
            $field = CodeEditorField::new($property)
                ->setLanguage('json');
        }

        if (
            $typeKey === self::TEXTAREA
        ) {
            $field = FormTypeField::new($property)
                ->setFormType(TextareaType::class);
        }

        if (
            $typeKey === self::WYSIWYG
        ) {
            $field = TinyMCEField::new($property);
        }

        if (
            $typeKey === self::IMAGE
        ) {
            $field = MediaField::new($property);
        }

        if (
            $typeKey === self::BOOLEAN
        ) {
            // une case à cocher ne peut pas exprimer « hériter / oui / non » : une surcharge « non » serait impossible
            $field = 'value' === $property
                ? CheckboxField::new($property)
                : FormTypeField::new($property)
                    ->setFormType(ChoiceType::class)
                    ->setFormTypeOption('choices', [
                        'sylius_happy_cms.config.admin.channel_value_choice.yes' => '1',
                        'sylius_happy_cms.config.admin.channel_value_choice.no' => '0',
                    ])
                    ->setFormTypeOption('placeholder', 'sylius_happy_cms.config.admin.channel_value_choice.inherit');
        }

        if (
            $typeKey === self::EMAIL
        ) {
            $field = FormTypeField::new($property)
                ->setFormType(EmailType::class);
        }

        if (
            $typeKey === self::DATE
        ) {
            $field = FormTypeField::new($property)
                ->setFormType(DateType::class);
        }

        if (
            $typeKey === self::DATETIME
        ) {
            $field = FormTypeField::new($property)
                ->setFormType(DateTimeType::class);
        }

        $field->setLabel($label ?? 'sylius_happy_cms.config.admin.type.' . $typeKey);
        $field->setDisabled(false);
        if ('value' !== $property) {
            $field->setRequired(false);
        }

        return $field;
    }
}
