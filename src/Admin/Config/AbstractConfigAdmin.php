<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Config;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ChoiceMaskField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ColumnField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusEasyCrudPlugin\Enum\ColumnSizeEnum;
use Adeliom\SyliusHappyCMSPlugin\Admin\Channel\ChannelsFieldTrait;
use Adeliom\SyliusHappyCMSPlugin\DataMapperInterface\ConfigTranslatableDataMapper;
use Adeliom\SyliusHappyCMSPlugin\Entity\Channel\ChannelAwareInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Config\ConfigTranslation;
use Adeliom\SyliusHappyCMSPlugin\Enum\Config\ConfigTypeEnum;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;

abstract class AbstractConfigAdmin extends AbstractAdmin implements ConfigAdminInterface
{
    use ChannelsFieldTrait;

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
        $builder->setDataMapper(new ConfigTranslatableDataMapper($this->propertyAccessor));
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        yield TabField::new('sylius_happy_cms.config.admin.tab.configuration')
            ->renderHorizontal();

        yield ColumnField::new('sylius_happy_cms.config.admin.tab.configuration')
            ->setSize(ColumnSizeEnum::WIDE_4_OF_12);

        yield Field::new('key', 'sylius_happy_cms.config.admin.field.key')
            ->setRequired(true);

        yield Field::new('name', 'sylius_happy_cms.config.admin.field.name')
            ->setRequired(true);

        yield Field::new('description', 'sylius_happy_cms.config.admin.field.description');

        yield $this->createChannelsField('sylius_happy_cms.config.admin.field.channels');

        yield ColumnField::new('sylius_happy_cms.config.admin.tab.value')
            ->setSize(ColumnSizeEnum::WIDE_6_OF_12);

        /** @var list<string> $typeKeys */
        $typeKeys = array_values(ConfigTypeEnum::toArray());
        $transTypeKeys = preg_filter('/^/', 'sylius_happy_cms.config.admin.type.', $typeKeys);

        yield ChoiceMaskField::new('type', 'sylius_happy_cms.config.admin.field.type')
            ->setRequired(true)
            ->renderExpanded(false)
            ->setChoices(array_combine($transTypeKeys, $typeKeys))
            ->setMap(array_combine($typeKeys, array_map(
                fn ($type) => [sprintf('config_admin_translations_%s', $type)],
                $typeKeys,
            )))
            ->isTranslation(true)
            ->hideOnIndex();

        $channels = $this->getEligibleChannels();

        foreach ($typeKeys as $typeKey) {
            $translationField = TranslationField::new(sprintf('translations_%s', $typeKey), 'sylius_happy_cms.config.admin.type.' . $typeKey)
                ->addField(
                    ConfigTypeEnum::getAdminField($typeKey),
                );

            // une valeur facultative par channel, du même type que la valeur par défaut (vide = pas de surcharge)
            foreach ($channels as $channel) {
                $translationField->addField(
                    ConfigTypeEnum::getAdminField(
                        $typeKey,
                        ConfigTranslation::CHANNEL_FIELD_PREFIX . $channel->getCode(),
                        new TranslatableMessage('sylius_happy_cms.config.admin.field.channel_value', ['%channel%' => $channel->getName() ?? $channel->getCode()]),
                    ),
                );
            }

            yield $translationField->onlyOnForms();
        }
    }

    /**
     * Channels pour lesquels une valeur propre peut être saisie : ceux rattachés à la config,
     * ou tous les channels Sylius si elle n'en a aucun (disponible partout).
     *
     * @return list<ChannelInterface>
     */
    private function getEligibleChannels(): array
    {
        $resource = $this->getResource();
        if ($resource instanceof ChannelAwareInterface && !$resource->isAvailableForAllChannels()) {
            return array_values($resource->getChannels()->toArray());
        }

        /** @var array<string, mixed> $resources */
        $resources = $this->parameterBag->get('sylius.resources');
        $channelClasses = is_array($resources['sylius.channel'] ?? null) ? ($resources['sylius.channel']['classes'] ?? null) : null;
        $modelClass = is_array($channelClasses) ? ($channelClasses['model'] ?? null) : null;
        if (!is_string($modelClass) || !class_exists($modelClass)) {
            return [];
        }

        /** @var list<ChannelInterface> $channels */
        $channels = $this->entityManager->getRepository($modelClass)->findBy([], ['code' => 'ASC']);

        return $channels;
    }
}
