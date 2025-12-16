<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Form;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusEasyCrudPlugin\Form\AdminFormTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;
use EmilePerron\TinymceBundle\Form\Type\TinymceType as BaseTinymceType;

class TinymceBridgeType extends BaseTinymceType implements AdminFormTypeInterface
{
    public function getBlockPrefix(): string
    {
        return 'happy_cms_tinymce';
    }

    /**
     * @return array<string, array<int,string|Asset>>
     */
    public static function configureAdminAssets(): array
    {
        return [
            'js' => [
                (Asset::new('tiny-mce.js'))->package(AssetHappyCMSPackage::PACKAGE_NAME),
            ],
            'css' => [
            ],
        ];
    }

    /**
     * @return string[]
     */
    public static function configureAdminFormThemes(): array
    {
        return [
            '@SyliusHappyCMSPlugin/field/tinymce/form.html.twig',
        ];
    }
}
