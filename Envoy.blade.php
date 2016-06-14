@servers(['server' => 'siacadmin@wspuertos.siacseguridad.com'])

@setup
    $project = "wspuertos";
@endsetup

@task('init', ['on' => 'server'])
    cd /var/www/
    git clone git@gitserver.siacseguridad.com:/var/git/sistemas/{{ "$project" }}.git
    cd {{ "$project" }}
    chmod -R ugo+w storage bootstrap/cache
    ln -s .env.produccion .env
    composer install
    npm install
    node_modules/gulp/bin/gulp.js --production
    # php artisan route:cache # no soporta closures
    php artisan config:cache
@endtask

@task('deploy', ['on' => 'server'])
    cd /var/www/{{ "$project" }}
    git fetch origin master
    git checkout FETCH_HEAD -- npm-shrinkwrap.json package.json
    echo
    echo "Se instalan dependencias npm. esto puede tardar..."
    npm install
    php artisan down
    git merge origin master
    composer install
    node_modules/gulp/bin/gulp.js --production
    # php artisan route:cache # no soporta closures
    php artisan config:cache
    php artisan up
@endtask
