<?php

use Laravel\Lumen\Testing\DatabaseTransactions;

use App\AvisoCliente;
use App\AvisoConfiguracion;
use App\Destinatario;

class SendMultipleAvisoTest extends TestCase
{
    use DatabaseTransactions;

    public function testSendMultipleAviso()
    {
        $movil = $this->getMovil();
        $waypoint = $this->getWaypoint();
        $aviso_cliente1 = AvisoCliente::create([
            'aviso_tipo_id' => 1, // entrada waypoint
            'cliente_id' => $movil->cliente_id,
        ]);
        $aviso_cliente2 = AvisoCliente::create([
            'aviso_tipo_id' => 1, // entrada waypoint
            'cliente_id' => $movil->cliente_id,
        ]);
        AvisoConfiguracion::firstOrCreate([
            'aviso_cliente_id' => $aviso_cliente1->id,
            'aviso_configuracion_tipo_id' => 1, // "waypoint id"
            'valor' => $waypoint->waypoint_id, // <waypoint_id>
        ]);
        $destinatarios = factory(App\Destinatario::class, 5)->create([
            'cliente_id' => $movil->cliente_id,
        ])->each(function($destinatario) use ($aviso_cliente1, $aviso_cliente2) {
            factory(App\AvisoDestinatario::class)->create([
                'aviso_cliente_id' => $aviso_cliente1->id,
                'destinatario_id' => $destinatario->id,
            ]);
            factory(App\AvisoDestinatario::class)->create([
                'aviso_cliente_id' => $aviso_cliente2->id,
                'destinatario_id' => $destinatario->id,
            ]);
        });

        $this->post('/eventos', [
            'evento_tipo_id' => self::ENTRADA_WAYPOINT,
            'movil_id' => $movil->movil_id,
            'waypoint_id' => $waypoint->waypoint_id,
            'cliente_id' => $movil->cliente_id,
            'dominio' => 'ABC123',
            'timestamp' => 123456,
        ]);
        $this->assertResponseStatus(201);
    }
}
