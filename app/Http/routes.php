<?php

Route::post('/reenvios', 'ReenvioController@store');
Route::patch('/reenvios/{id}', 'ReenvioController@update');
