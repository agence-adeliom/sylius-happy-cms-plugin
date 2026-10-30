<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Channel;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;

/**
 * Champ « Channels » commun aux admins des entités rattachables à des channels.
 * Vide = disponible sur tous les channels.
 */
trait ChannelsFieldTrait
{
    protected function createChannelsField(string $labelKey): Field
    {
        return Field::new('channels', $labelKey)
            ->setFormType(ChannelChoiceType::class)
            ->setFormTypeOption('multiple', true)
            ->setRequired(false)
            ->setHelp('sylius_happy_cms.admin.channels.help')
            ->hideOnIndex();
    }
}
