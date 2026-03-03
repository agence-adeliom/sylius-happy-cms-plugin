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

    .addEntry('flexible-content', './assets/flexible-content/flexible-content.js')
    .addEntry('media-form', './assets/media/js/app.js')
    .addEntry('seo-block-type', './assets/blocks/seo-block-type.js')
    .addEntry('accordion-block-type', './assets/blocks/accordion-block-type.js')
    .addEntry('tiny-mce', './assets/tinymce/field.js')
    .addEntry('page-builder', './assets/page-builder/entrypoint.js')
;

const webpackConfig = Encore.getWebpackConfig();

// Configure webpack to use browser build for plyr instead of ES module sources
webpackConfig.resolve.alias = {
    ...webpackConfig.resolve.alias,
    'plyr': path.resolve(__dirname, 'node_modules/plyr/dist/plyr.min.js')
};

module.exports = webpackConfig;
