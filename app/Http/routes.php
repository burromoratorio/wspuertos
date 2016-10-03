<?php

$app->get('reenvios', 'ReenvioController@index');
$app->post('reenvios', 'ReenvioController@store');
$app->patch('reenvios/{id}', 'ReenvioController@update');

$app->get('eventos', 'EventoController@index');
$app->post('eventos', 'EventoController@store');

$app->patch('avisos/{id}', 'AvisoController@update');
