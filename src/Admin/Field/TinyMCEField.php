<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Adeliom\SyliusHappyCMSPlugin\Form\TinymceBridgeType;

class TinyMCEField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        $field = (new self());
        $field
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setGridTemplatePath('@SyliusHappyCMSPlugin/media/field/tinymce/grid.html.twig')
            ->setShowTemplatePath('@SyliusHappyCMSPlugin/media/field/tinymce/show.html.twig')
            ->addFormThemes(TinymceBridgeType::configureAdminFormThemes())
            ->addJsFiles(TinymceBridgeType::configureAdminAssets()['js'])
            ->addCssFiles(TinymceBridgeType::configureAdminAssets()['css'])
            ->setFormType(TinymceBridgeType::class)
            ->addCssClass('field-happy-cms-media')
        ;

        return $field;
    }

    /**
     * @param array{
     * toolbar: string,
     * skin: ?string,
     * content_css: ?string,
     * content_style: ?string,
     * config: ?string,
     * plugins: ?string,
     * toolbar: ?string,
     * toolbar_mode: ?string,
     * menubar: ?string,
     * contextmenu: ?string,
     * quickbars_insert_toolbar: ?string,
     * quickbars_selection_toolbar: ?string,
     * resize: ?string,
     * icons: ?string,
     * icons_url: ?string,
     * setup: ?string,
     * images_upload_url: ?string,
     * images_upload_route: ?string,
     * images_upload_route_params: ?array,
     * images_upload_handler: ?string,
     * images_upload_base_path: ?string,
     * images_upload_credentials: ?boolean,
     * images_reuse_filename: ?string,
     * powerpaste_word_import: ?string,
     * powerpaste_html_import: ?string,
     * powerpaste_allow_local_images: ?string,
     * } $attr
     */
    public function setAttr(array $attr): void
    {
        $this->setFormTypeOption('attr', $attr);
    }
}
