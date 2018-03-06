var Encore = require('@symfony/webpack-encore');

Encore
    // the project directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')
    .enableSourceMaps(!Encore.isProduction())
    // uncomment to create hashed filenames (e.g. app.abc123.css)
    // .enableVersioning(Encore.isProduction())

    // uncomment to define the assets of the project
    .addEntry('js/entryAdd', './assets/js/entryAdd.js')
    .addEntry('js/entryEdit', './assets/js/entryEdit.js')
    .addEntry('js/entrySearch', './assets/js/entrySearch.js')
    .addEntry('js/fac-set', './assets/js/fac-set.js')
    .addEntry('js/logRowsCount', './assets/js/logRowsCount.js')
    .addEntry('js/logSearch', './assets/js/logSearch.js')
    .addEntry('js/menuScript', './assets/js/menuScript.js')
    .addStyleEntry('css/common', './assets/css/common.scss')
    .addStyleEntry('css/header', './assets/css/header.scss')
    .addStyleEntry('css/entries', './assets/css/entries.scss')
    .addStyleEntry('css/entry-add', './assets/css/entry-add.scss')
    .addStyleEntry('css/fac-set', './assets/css/fac-set.scss')
    .addStyleEntry('css/logs', './assets/css/logs.scss')
    .addStyleEntry('css/security', './assets/css/security.scss')

    // uncomment if you use Sass/SCSS files
     .enableSassLoader()

    // uncomment for legacy applications that require $/jQuery as a global variable
    .autoProvidejQuery()

     // empty the outputPath dir before each build
    .cleanupOutputBeforeBuild()

    // show OS notifications when builds finish/fail
    .enableBuildNotifications()
;

module.exports = Encore.getWebpackConfig();
