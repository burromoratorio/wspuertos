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
    composer install --production
    npm install --production
    node_modules/gulp/bin/gulp.js --production
    # php artisan route:cache # no soporta closures
    php artisan config:cache
@endtask

@task('deploy', ['on' => 'server'])

    cd /var/www/{{ "$project" }}
    git fetch origin master

    echo "--- Se instalan/actualizan dependencias (npm). Esto puede tardar... ---"
    git checkout FETCH_HEAD -- npm-shrinkwrap.json package.json
    npm install --production

    php artisan down

    echo "--- Se instalan/actualizan dependencias (composer) ---"
    git checkout FETCH_HEAD -- composer.json composer.lock
    composer install

    echo "--- Se compilan assets, se actualiza aplicación, se crean caches ---"
    git merge origin master
    node_modules/gulp/bin/gulp.js --production
    # php artisan route:cache # no soporta closures
    php artisan config:cache

    php artisan up

@endtask
