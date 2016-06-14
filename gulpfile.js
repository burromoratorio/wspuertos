var elixir = require('laravel-elixir');

elixir(function(mix) {
    mix.scripts([
        '../../../node_modules/jquery/dist/jquery.js',
        '../../../node_modules/bootstrap-sass/assets/javascripts/bootstrap.js',
    ]);
    mix.sass(['app.scss'], 'resources/assets/css/');
    mix.styles([
        'app.css',
    ]);
    mix.copy([
        'node_modules/bootstrap-sass/assets/fonts/',
    ], 'public/fonts/');
});
