var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('./resources/public/')
    .setPublicPath('/bundles/syliushappycmsplugin/')
    .setManifestKeyPrefix('')

    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .disableSingleRuntimeChunk()
    .enableSassLoader()
    .enableReactPreset()
    .enablePostCssLoader()

    .copyFiles({
        from: './assets/blocks/dist',
        to: 'dist/[path][name].[ext]',
    })

    .addEntry('flexible-content', './assets/flexible-content/flexible-content.js')
    .addEntry('media-form', './assets/media/js/app.js')
    .addEntry('seo-block-type', './assets/blocks/seo-block-type.js')
    .addEntry('accordion-block-type', './assets/blocks/accordion-block-type.js')
    .addEntry('tiny-mce', './assets/tinymce/field.js')
    .addEntry('page-builder', './assets/page-builder/entrypoint.js')
;

module.exports = Encore.getWebpackConfig();
