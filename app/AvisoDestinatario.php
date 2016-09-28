<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AvisoDestinatario extends Model
{
    protected $table = 'avisos_destinatarios';

    protected function destinatario() {
        return $this->belongsTo('App\Destinatario', 'destinatario_id');
    }
}
