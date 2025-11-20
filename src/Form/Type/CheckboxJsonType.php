<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Adeliom\SyliusHappyCMSPlugin\Form\Type;

use Adeliom\SyliusHappyCMSPlugin\Form\DataTransformer\BooleanToStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class CheckboxJsonType extends CheckboxType
{
    /**
     * @param array{
     *     value: string,
     *     false_values: mixed[]
     * } $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder->resetViewTransformers();
        $builder->addViewTransformer(
            new BooleanToStringTransformer($options['value'], $options['false_values']),
        );
    }
}
