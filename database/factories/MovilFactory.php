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

$factory->define(App\Waypoint::class, function (Faker\Generator $faker) {
    return [
        'nombre' => $faker->name,
    ];
});

$factory->define(App\AvisoCliente::class, function (Faker\Generator $faker) {
    return [
        'aviso_tipo_id' => 1, // entrada waypoint
        'cliente_id' => function () {
            return factory(App\Cliente::class)->create()->cliente_id;
        },
    ];
});

$factory->define(App\AvisoConfiguracion::class, function (Faker\Generator $faker) {
    return [
        'aviso_cliente_id' => function () {
            return factory(App\AvisoCliente::class)->create()->id;
        },
        'aviso_configuracion_tipo' => 1, // "waypoint id"
        'valor' => function () {
            return factory(App\Waypoint::class)->create()->waypoint_id;
        },
    ];
});

$factory->define(App\Destinatario::class, function (Faker\Generator $faker) {
    return [
        'cliente_id' => function () {
            return factory(App\Cliente::class)->create()->id;
        },
        'mail' => $faker->email,
    ];
});

$factory->define(App\AvisoDestinatario::class, function (Faker\Generator $faker) {
    return [
        'aviso_cliente_id' => function () {
            return factory(App\AvisoCliente::class)->create()->id;
        },
        'destinatario_id' => function () {
            return factory(App\Destinatario::class)->create()->id;
        },
    ];
});
