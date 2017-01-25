<?php

$factory->define(App\Localidad::class, function (Faker\Generator $faker) {
    return [
        'provincia_id' => 1,
        'codigo_postal' => 2700,
        'localidad' => 'PERGAMINO',
        'es_ES' => 'Pergamino',
        'en_US' => 'Pergamino',
        'pt_PT' => 'Pergamino',
        'latitud' => 33.9000015259,
        'longitud'  => -60.5833320618,
    ];
});

$factory->define(App\Cliente::class, function (Faker\Generator $faker) {
    return [
        'razon_social' => $faker->name,
        'localidad_id' => function () {
            return factory(App\Localidad::class)->create()->localidad_id;
        }
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
        'aviso_configuracion_tipo_id' => 1, // "waypoint id"
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

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'username' => $faker->name,
        'email' => $faker->email,
        'password' => $faker->password,
    ];
});

$factory->define(App\ReenvioHost::class, function (Faker\Generator $faker) {
    return [
        'nombre' => $faker->name,
        'destino' => '0.0.0.0',
        'puerto' => 1234,
        'protocolo' => 'TCP',
    ];
});

$factory->define(App\ReenvioMovil::class, function (Faker\Generator $faker) {
    return [
        'movil_id' => 1,/*function () {
            return factory(App\Movil::class)->create()->id;
        },*/
        'reenvio_host_id' => function () {
            return factory(App\ReenvioHost::class)->create()->id;
        },
        'usuario_id' => function () {
            return factory(App\User::class)->create()->id;
        },
    ];
});

$factory->state(App\ReenvioMovil::class, 'udp', function ($faker) {
    return [
        'reenvio_host_id' => function () {
            return factory(App\ReenvioHost::class)->create([
                'protocolo' => 'UDP',
            ])->id;
        },
    ];
});
