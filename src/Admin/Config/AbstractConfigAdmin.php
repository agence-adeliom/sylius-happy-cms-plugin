<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Config;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ChoiceMaskField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusHappyCMSPlugin\DataMapperInterface\ConfigTranslatableDataMapper;
use Adeliom\SyliusHappyCMSPlugin\Enum\Config\ConfigTypeEnum;
use Symfony\Component\Form\FormBuilderInterface;

abstract class AbstractConfigAdmin extends AbstractAdmin implements ConfigAdminInterface
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getDefaultSortColumn(): string
    {
        return 'name';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder->setDataMapper(new ConfigTranslatableDataMapper($this->crudAdminFactory->getPropertyAccessor()));
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        yield TabField::new('sylius_happy_cms.config.admin.tab.configuration')
            ->renderHorizontal();

        yield Field::new('key', 'sylius_happy_cms.config.admin.field.key')
            ->setRequired(true);

        yield Field::new('name', 'sylius_happy_cms.config.admin.field.name')
            ->setRequired(true);

        yield Field::new('description', 'sylius_happy_cms.config.admin.field.description');

        $typeKeys = array_values(ConfigTypeEnum::toArray());
        $transTypeKeys = preg_filter('/^/', 'sylius_happy_cms.config.admin.type.', $typeKeys);

        yield ChoiceMaskField::new('type', 'sylius_happy_cms.config.admin.field.type')
            ->setRequired(true)
            ->renderExpanded(false)
            ->setChoices(array_combine($transTypeKeys, $typeKeys))
            ->setMap(array_combine($typeKeys, array_map(fn ($type) => [sprintf('translations_%s', $type)], $typeKeys)))
            ->isTranslation(true)
            ->hideOnIndex();

        foreach ($typeKeys as $typeKey) {
            yield TranslationField::new(sprintf('translations_%s', $typeKey), 'sylius_happy_cms.config.admin.type.' . $typeKey)
                ->addField(
                    ConfigTypeEnum::getAdminField($typeKey),
                )
                ->hideOnIndex();
        }
    }
}
