var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('./public/')
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

    .addEntry('flexible-content', './assets/js/flexible-content.js')
    .addEntry('media-form', './assets/media/js/app.js')
;

module.exports = Encore.getWebpackConfig();
