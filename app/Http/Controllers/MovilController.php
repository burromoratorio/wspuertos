<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Movil;

class MovilController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getMovilesCliente($id)
    {
        return Movil::select('moviles.movil_id', 'moviles.alias', 'moviles.dominio')
                    ->where('moviles.cliente_id', $id)
                    ->orWhere('moviles.fletero_id', $id)
                    ->orderBy('moviles.alias')
                    ->get();

    }

}
