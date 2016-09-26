<?php

use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Movil;

class CreateEventTest extends TestCase
{
    use DatabaseTransactions;

    const ENTRADA_WAYPOINT=1;
    const SALIDA_WAYPOINT=2;

    public function testInWaypoint()
    {
        $movil = $this->getMovil();
        $this->post('/eventos', [
            'evento_tipo_id' => self::ENTRADA_WAYPOINT,
            'movil_id' => $movil->movil_id,
            'waypoint_id' => 1,
            'cliente_id' => 1,
        ]);
        $this->assertResponseOk();
    }

    public function testOutWaypoint()
    {
        $movil = $this->getMovil();
        $this->post('/eventos', [
            'evento_tipo_id' => self::SALIDA_WAYPOINT,
            'movil_id' => $movil->movil_id,
            'waypoint_id' => 1,
            'cliente_id' => $movil->cliente_id,
        ]);
        $this->assertResponseOk();
    }

    private function getMovil()
    {
        $movil = Movil::first();
        if (!$movil) {
            $movil = factory(App\Movil::class)->create();
        }
        print("\nSe prueba con el movil: ".$movil->movil_id."\n");
        return $movil;
    }
}
