<?php

use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Movil;

class CreateEventTest extends TestCase
{
    //use DatabaseTransactions;

    public function testInWaypoint()
    {
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
        $this->assertEquals($this->response->getContent(), "OK");
    }

    public function testOutWaypoint()
    {
        $movil = $this->getMovil();
        $waypoint = $this->getWaypoint();
        $this->post('/eventos', [
            'evento_tipo_id' => self::SALIDA_WAYPOINT,
            'movil_id' => $movil->movil_id,
            'waypoint_id' => $waypoint->waypoint_id,
            'cliente_id' => $movil->cliente_id,
            'dominio' => 'ABC123',
            'timestamp' => 123456,
        ]);
        $this->assertEquals($this->response->getContent(), "OK");
    }
}
