<?php declare(strict_types=1);

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

if (
    isset($classNameDetail) && $classNameDetail instanceof ClassNameDetails &&
    isset($relationClassNameDetail, $scope, $hasFlexibleContent)
) {
    $mainClassData = [
        'singular' => mb_strtolower(Str::asSnakeCase($classNameDetail->getShortName())),
        'plural' => mb_strtolower(Str::asSnakeCase(Str::singularCamelCaseToPluralCamelCase($classNameDetail->getShortName()))),
    ];
    if ($relationClassNameDetail instanceof ClassNameDetails) {
        $relationClassData = [
            'singular' => mb_strtolower(Str::asSnakeCase($relationClassNameDetail->getShortName())),
            'plural' => mb_strtolower(Str::asSnakeCase(Str::singularCamelCaseToPluralCamelCase($relationClassNameDetail->getShortName()))),
        ];
    }
    ?>
<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= str_replace('Entity', 'Admin', Str::getNamespace($classNameDetail->getFullName())) ?>;

use App\Entity\HappyCMS\<?= ucfirst($scope) ?>\<?= $classNameDetail->getShortName() ?>;
use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\EnumField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\FormTypeField;
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\SEOField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ColumnField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\SlugField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ResourceChoiceField;
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\FlexibleContentField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\Enum\ColumnSizeEnum;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints\Length;

final class <?= $classNameDetail->getShortName() ?>Admin extends AbstractAdmin implements ServiceSubscriberInterface
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'happy_cms_admin_<?= mb_strtolower($scope) ?>_<?= mb_strtolower($classNameDetail->getShortName()) ?>';
    }

    public static function getEntityFqcn(): string
    {
        return <?= $classNameDetail->getShortName() ?>::class;
    }

    public static function getDefaultSortColumn(): string
    {
        return 'name';
    }

<?php if ($hasFlexibleContent) { ?>
    public function configureActions(string $pageName): Actions
    {
        $actions = parent::configureActions($pageName);
        $contentAction = Action::new('content', 'happy_cms.page.admin.action.manage_content', 'bi:book')
            ->linkToRoute('happy_cms_admin_page_content_builder', [
                'id' => '$resource.getId()',
            ]);
        //$actions->addItemAction(Crud::PAGE_INDEX, $contentAction);
        $actions->addItemAction(Crud::PAGE_DETAIL, $contentAction);
        $actions->addItemAction(Crud::PAGE_EDIT, $contentAction);

        return $actions;
    }
<?php } ?>

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        if (is_null($context)) {

            yield TabField::new('<?= $classNameDetail->getShortName() ?>', 'happy_cms.<?= $scope ?>.admin.tab.<?=
        mb_strtolower($classNameDetail->getShortName()) ?>')
                ->renderHorizontal();

            yield ColumnField::new('happy_cms.<?= $scope ?>.admin.panel.metadata')
                ->setSize(ColumnSizeEnum::WIDE_6_OF_12);

            yield Field::new('name')
                ->setLabel('<?= $classNameDetail->getShortName() ?>')
                ->setSortablePath('translations.name')
                ->onlyOnIndex();

        <?php if ($relationClassNameDetail instanceof ClassNameDetails && is_array($relationClassData)) { ?>
            yield ResourceChoiceField::new('<?= $relationClassData['plural'] ?>', '<?= $relationClassData['plural'] ?>')
                ->setResource('happy_cms.<?= $scope ?>_<?= $relationClassData['singular'] ?>')
                ->setMultiple()
                ->setChoiceValue('id')
                ->setChoiceName('name')
                ->setRepositoryMethod('findByPhrase')
                ->setRemoteCriteriaName('phrase')
                ->setRepositoryArguments([
                    'phrase' => '$phrase',
                    'locale' => "expr:service('sylius.context.locale').getLocaleCode()",
                    'limit' => 10,
                    'fieldName' => 'name',
                ]);
        <?php } ?>

            yield TranslationField::new('translations')
                ->addField(
                    Field::new('name')
                        ->setDisabled(false)
                        ->setRequired(true)
                        ->setFormTypeOption('constraints', [
                            new Length(['min' => 1])
                        ])
                )
                ->addField(
                    SlugField::new('slug')
                        ->setRequired(true)
                        ->setFormTypeOption('constraints', [
                            new Length(['min' => 1])
                        ])
                )
        <?php
            if (!empty($extraFields)) {
                foreach ($extraFields as $fieldData) {
                    ?>
                ->addField(
                    SlugField::new('<?= $fieldData['name'] ?>', '<?= $fieldData['name'] ?>')
                        ->setRequired(true)
                        ->setFormTypeOption('constraints', [
                            new Length(['min' => 1])
                        ])
                )
                <?php
                }
            }
    ?>
                ->hideOnIndex();

            yield ColumnField::new('happy_cms.<?= $scope ?>.admin.panel.publication')
                ->setSize(ColumnSizeEnum::WIDE_6_OF_12);

            yield FormTypeField::new('publishDate', 'Date de publication', DateTimeType::class)
                ->setFormTypeOption('widget', 'single_text')
                ->setFormTypeOption('html5', 'true')
                ->hideOnIndex();

            yield FormTypeField::new('unpublishDate')
                ->setFormType(DateTimeType::class)
                ->setFormTypeOption('widget', 'single_text')
                ->setFormTypeOption('html5', 'true')
                ->hideOnIndex();

            yield EnumField::new('publishState')
                ->setEnum(ThreeStateStatusEnum::class)
                ->setRequired(false)
                ->setFormTypeOption('placeholder', false)
                ->renderExpanded()
                ->hideOnIndex();

            yield TabField::new('seo', 'happy_cms.page.admin.tab.seo');

            yield TranslationField::new('seoTranslations', 'happy_cms.page.admin.field.seo.translations')
                ->addField(
                    SEOField::new('seo', 'happy_cms.page.admin.field.seo')
                        ->setDisabled(false)
                        ->setRequired(true)
                )
                ->hideOnIndex();
        }
    }
}

<?php } ?>
