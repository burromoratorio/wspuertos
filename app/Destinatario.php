<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Destinatario extends Model
{
    protected $table = 'destinatarios';
    protected $dateFormat = 'Y-m-d H:i:s';
}
