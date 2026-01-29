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
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

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

    public static function getAdminField(string $typeKey): FieldInterface
    {
        $field = Field::new('value');

        if (
            $typeKey === self::CODE ||
            $typeKey === self::JSON
        ) {
            $field = CodeEditorField::new('value')
                ->setLanguage('json');
        }

        if (
            $typeKey === self::TEXTAREA
        ) {
            $field = FormTypeField::new('value')
                ->setFormType(TextareaType::class);
        }

        if (
            $typeKey === self::WYSIWYG
        ) {
            $field = TinyMCEField::new('value');
        }

        if (
            $typeKey === self::IMAGE
        ) {
            $field = MediaField::new('value');
        }

        if (
            $typeKey === self::BOOLEAN
        ) {
            $field = CheckboxField::new('value');
        }

        if (
            $typeKey === self::EMAIL
        ) {
            $field = FormTypeField::new('value')
                ->setFormType(EmailType::class);
        }

        if (
            $typeKey === self::DATE
        ) {
            $field = FormTypeField::new('value')
                ->setFormType(DateType::class);
        }

        if (
            $typeKey === self::DATETIME
        ) {
            $field = FormTypeField::new('value')
                ->setFormType(DateTimeType::class);
        }

        $field->setLabel('sylius_happy_cms.config.admin.type.' . $typeKey);
        $field->setDisabled(false);

        return $field;
    }
}
