<?php

use App\Movil;
use App\Cliente;
use App\Waypoint;

class TestCase extends Laravel\Lumen\Testing\TestCase
{
    const ENTRADA_WAYPOINT=1;
    const SALIDA_WAYPOINT=2;

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    protected function getMovil()
    {
        $movil = Movil::first();
        if (!$movil) {
            $movil = factory(App\Movil::class)->create();
        }
        print("\nSe prueba con el movil: ".$movil->movil_id."\n");
        return $movil;
    }

    protected function getCliente()
    {
        $cliente = Cliente::first();
        if (!$cliente) {
            $cliente = factory(App\Cliente::class)->create();
        }
        print("\nSe prueba con el cliente: ".$cliente->cliente_id."\n");
        return $cliente;
    }

    protected function getWaypoint()
    {
        $waypoint = Waypoint::first();
        if (!$waypoint) {
            $waypoint = factory(App\Waypoint::class)->create();
        }
        print("\nSe prueba con el waypoint: ".$waypoint->waypoint_id."\n");
        return $waypoint;
    }
}
