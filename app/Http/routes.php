<?php

$app->get('reenvios', 'ReenvioController@index');
$app->post('reenvios', 'ReenvioController@store');
$app->patch('reenvios/{id}', 'ReenvioController@update');
$app->post('udpTest/{msg}', 'ReenvioController@testPlatform');

$app->get('eventos', 'EventoController@index');
$app->post('eventos', 'EventoController@store');

$app->patch('avisos/{id}', 'AvisoController@update');

$app->get('destinatarios', 'DestinatarioController@index');
$app->post('destinatarios', 'DestinatarioController@store');
$app->patch('destinatarios/{id}', 'DestinatarioController@update');
$app->delete('destinatarios/{id}', 'DestinatarioController@destroy');

$app->post('nuevoViaje', 'ViajeController@create');
$app->get('viajeActivo/{id}/{cliente}', 'ViajeController@getViajes');

$app->get('avisos-clientes', 'AvisoClienteController@index');
$app->get('avisos-clientes/{id}', 'AvisoClienteController@show');
$app->post('avisos-clientes', 'AvisoClienteController@store');
$app->patch('avisos-clientes/{id}', 'AvisoClienteController@update');
$app->delete('avisos-clientes/{id}', 'AvisoClienteController@destroy');


$app->get('destinatarios/cliente/{id}', 'DestinatarioController@getDestinatariosCliente');
$app->get('moviles/cliente/{id}', 'MovilController@getMovilesCliente');
$app->get('waypoints/cliente/{id}', 'WaypointClienteController@getWaypointsCliente');
