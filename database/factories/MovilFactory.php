<?php

$factory->define(App\Cliente::class, function (Faker\Generator $faker) {
    return [
        'razon_social' => $faker->name,
    ];
});

$factory->define(App\Movil::class, function (Faker\Generator $faker) {
    return [
        'cliente_id' => function () {
            return factory(App\Cliente::class)->create()->cliente_id;
        }
    ];
});
