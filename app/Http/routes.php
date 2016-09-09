<?php

$app->get('/reenvios', 'ReenvioController@index');
$app->post('/reenvios', 'ReenvioController@store');
$app->patch('/reenvios/{id}', 'ReenvioController@update');
