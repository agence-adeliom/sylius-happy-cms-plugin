<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsBlock;
use Symfony\Component\Form\AbstractType;

#[AsBlock(name: 'Plain', icon: 'ti ti-star', template: 'blocks/plain.html.twig')]
final class AttributeOnPlainFormType extends AbstractType
{
}
