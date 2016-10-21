<?php

$app->get('reenvios', 'ReenvioController@index');
$app->post('reenvios', 'ReenvioController@store');
$app->patch('reenvios/{id}', 'ReenvioController@update');

$app->get('eventos', 'EventoController@index');
$app->post('eventos', 'EventoController@store');

$app->patch('avisos/{id}', 'AvisoController@update');

$app->get('destinatarios', 'DestinatarioController@index');
$app->post('destinatarios', 'DestinatarioController@store');
$app->patch('destinatarios/{id}', 'DestinatarioController@update');
$app->delete('destinatarios/{id}', 'DestinatarioController@destroy');
