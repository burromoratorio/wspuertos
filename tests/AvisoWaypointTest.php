<?php

use Laravel\Lumen\Testing\DatabaseTransactions;

use App\AvisoCliente;
use App\AvisoConfiguracion;
use App\Destinatario;
use App\Events\AvisoCreated;
use Illuminate\Support\Facades\Redis;

class AvisoWaypointTest extends TestCase
{
    use DatabaseTransactions;

    const ESTADO_PENDIENTE = 1;
    const ESTADO_ENVIADO = 2;
    const ESTADO_FALLIDO = 3;

    public function testSendAviso()
    {
        $movil = $this->getMovil();
        $waypoint = $this->getWaypoint();
        $aviso_cliente = AvisoCliente::firstOrCreate([
            'aviso_tipo_id' => 1, // entrada waypoint
            'cliente_id' => $movil->cliente_id,
        ]);
        AvisoConfiguracion::firstOrCreate([
            'aviso_cliente_id' => $aviso_cliente->id,
            'aviso_configuracion_tipo_id' => 1, // "waypoint id"
            'valor' => $waypoint->waypoint_id, // <waypoint_id>
        ]);
        $destinatarios = factory(App\Destinatario::class, 5)->create([
            'cliente_id' => $movil->cliente_id,
        ])->each(function($destinatario) use ($aviso_cliente) {
            factory(App\AvisoDestinatario::class)->create([
                'aviso_cliente_id' => $aviso_cliente->id,
                'destinatario_id' => $destinatario->id,
            ]);
        });

        $this->expectsEvents(AvisoCreated::class);
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

    public function testUpdateAvisoPendiente() {
        Redis::shouldReceive('publish')->once();
        $aviso = factory(App\Aviso::class)->create();
        factory(App\AvisoDestinatario::class)->create([
            'aviso_cliente_id' => $aviso->aviso_cliente_id,
        ]);
        $id = $aviso->id;

        $this->patch('/avisos/'.$id, [
            'estado_envio_id' => static::ESTADO_PENDIENTE,
        ]);
        $this->seeInDatabase('avisos', [
            'id' => $id,
            'estado_envio_id' => static::ESTADO_PENDIENTE,
        ]);
        $this->assertResponseStatus(200);
    }

    public function testUpdateAvisoEnviado() {
        Redis::shouldReceive('publish')->never();
        $id = factory(App\Aviso::class)->create()->id;

        $this->patch('/avisos/'.$id, [
            'estado_envio_id' => static::ESTADO_ENVIADO,
        ]);
        $this->seeInDatabase('avisos', [
            'id' => $id,
            'estado_envio_id' => static::ESTADO_ENVIADO,
        ]);
        $this->assertResponseStatus(200);
    }

    public function testUpdateAvisoFallido() {
        Redis::shouldReceive('publish')->never();
        $id = factory(App\Aviso::class)->create()->id;

        $this->patch('/avisos/'.$id, [
            'estado_envio_id' => static::ESTADO_FALLIDO,
        ]);
        $this->seeInDatabase('avisos', [
            'id' => $id,
            'estado_envio_id' => static::ESTADO_FALLIDO,
        ]);
        $this->assertResponseStatus(200);
    }
}
