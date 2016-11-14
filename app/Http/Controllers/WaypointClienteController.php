<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Waypoint;

class WaypointClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getWaypointsCliente($id)
    {
        return Waypoint::join('waypoints_clientes', 'waypoints_clientes.waypoint_id', '=', 'waypoints.waypoint_id')
                       ->join('localidades', 'localidades.localidad_id', '=', 'waypoints.localidad_id' )
                       ->join('provincias', 'provincias.provincia_id', '=', 'localidades.provincia_id' )
                       ->distinct()
                       ->select('waypoints.waypoint_id', 'waypoints.nombre', 'localidades.localidad', 'provincias.provincia' )
                       ->orderBy('waypoints.nombre')
                       ->where('waypoints_clientes.cliente_id', $id)
                       ->get();

    }
}
