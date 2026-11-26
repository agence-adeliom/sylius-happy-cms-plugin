var Encore = require('@symfony/webpack-encore');
const path = require("path");

Encore
    .setOutputPath('./resources/public/')
    .setPublicPath('/bundles/syliushappycmsplugin/')
    .setManifestKeyPrefix('')

    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .disableSingleRuntimeChunk()
    .enableSassLoader()
    .enableVueLoader()
    .enablePostCssLoader()

    .copyFiles({
        from: './assets/media/dist',
        to: 'dist/[path][name].[ext]',
    })

    .copyFiles({
        from: './assets/blocks/dist',
        to: 'dist/[path][name].[ext]',
    })

    // Empty files served instead of media-form.js / media-form.css with easy-media-manager (AssetHappyCMSPackage)
    .copyFiles({
        from: './assets/easy-media/public',
        to: 'easy-media/[name].[ext]',
    })

    .addEntry('flexible-content', './assets/flexible-content/flexible-content.js')
    .addEntry('media-form', './assets/media/js/app.js')
    .addEntry('seo-block-type', './assets/blocks/seo-block-type.js')
    .addEntry('accordion-block-type', './assets/blocks/accordion-block-type.js')
    .addEntry('tiny-mce', './assets/tinymce/field.js')
    .addEntry('page-builder', './assets/page-builder/entrypoint.js')
    // Injected by PreviewResponseListener into signed page builder previews
    .addEntry('page-builder-preview', './assets/page-builder/preview-agent.js')
;

const webpackConfig = Encore.getWebpackConfig();

// Configure webpack to use browser build for plyr instead of ES module sources
webpackConfig.resolve.alias = {
    ...webpackConfig.resolve.alias,
    'plyr': path.resolve(__dirname, 'node_modules/plyr/dist/plyr.min.js')
};

module.exports = webpackConfig;
