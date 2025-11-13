<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config;

use Adeliom\SyliusHappyCMSPlugin\Entity\Config\ConfigTranslation as BaseConfigTranslation;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

#[Serializer\ExclusionPolicy('ALL')]
#[ORM\Entity]
#[ORM\Table(name: 'sylius_happy_cms__config_translation')]
class ConfigTranslation extends BaseConfigTranslation
{
}
