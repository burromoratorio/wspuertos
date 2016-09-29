<?php

use Laravel\Lumen\Testing\DatabaseTransactions;

use App\Cliente;
use App\AvisoCliente;
use App\AvisoConfiguracion;

class SendAvisoTest extends TestCase
{
    use DatabaseTransactions;

    public function testSendAviso()
    {
        $movil = $this->getMovil();
        $waypoint = $this->getWaypoint();

        $aviso_cliente = AvisoCliente::create([
            'aviso_tipo_id' => 1, // entrada waypoint
            'cliente_id' => $movil->cliente_id,
        ]);
        $aviso_configuracion = AvisoConfiguracion::create([
            'aviso_cliente_id' => $aviso_cliente->id,
            'aviso_configuracion_tipo' => 1, // "waypoint id"
            'valor' => $waypoint->waypoint_id, // <waypoint_id>
        ]);

        $movil = $this->getMovil();
        $waypoint = $this->getWaypoint();
        $this->post('/eventos', [
            'evento_tipo_id' => self::ENTRADA_WAYPOINT,
            'movil_id' => $movil->movil_id,
            'waypoint_id' => $waypoint->waypoint_id,
            'cliente_id' => $movil->cliente_id,
            'dominio' => 'ABC123',
            'timestamp' => 123456,
        ]);
        $this->assertTrue(true);
    }
}
