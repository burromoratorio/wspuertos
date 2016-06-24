var elixir = require('laravel-elixir');

elixir(function(mix) {
    mix.scripts([
        '../../../node_modules/jquery/dist/jquery.js',
        '../../../node_modules/bootstrap-sass/assets/javascripts/bootstrap.js',
        '../../../node_modules/angular/angular.js',
        '../../../node_modules/angular-ui-bootstrap/dist/ui-bootstrap.js',
        '../../../node_modules/angular-ui-bootstrap/dist/ui-bootstrap-tpls.js',
        '../../../node_modules/angular-i18n/angular-locale_es-ar.js',
        'app/app.js',
        'app/reenvios/**/*.js',
    ]);
    mix.sass(['app.scss'], 'resources/assets/css/');
    mix.styles([
        'app.css',
    ]);
    mix.copy([
        'node_modules/bootstrap-sass/assets/fonts/',
    ], 'public/fonts/');
});
