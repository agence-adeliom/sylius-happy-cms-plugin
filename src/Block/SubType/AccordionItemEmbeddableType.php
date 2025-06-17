<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Block\SubType;

use Adeliom\SyliusHappyCMSPlugin\Form\TinymceBridgeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AccordionItemEmbeddableType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class, [
            'required' => false,
            'label' => 'admin.blocks.accordion.fields.item.title',
        ]);

        $builder->add('content', TinymceBridgeType::class, [
            'required' => false,
            'label' => 'admin.blocks.accordion.fields.item.content',
        ]);
    }
}
