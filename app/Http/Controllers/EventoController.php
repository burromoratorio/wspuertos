<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Evento;
use App\AvisoCliente;
use Illuminate\Support\Facades\Redis;

class EventoController extends Controller
{
    public function index(Request $request) {
        return Evento::take(30)->get();
    }

    public function store(Request $request) {
        $this->validate($request, [
            'evento_tipo_id' => 'required|numeric',
            'movil_id' => 'required|numeric',
            'waypoint_id' => 'required|numeric',
            'cliente_id' => 'required|numeric',
        ]);
        $evento = Evento::create([
            'evento_tipo_id' => $request->input('evento_tipo_id'),
            'movil_id' => $request->input('movil_id'),
            'eventable_id' => $request->input('waypoint_id'),
            'eventable_type' => 'App\Waypoint',
        ]);
        return "OK\n";
    }
}
