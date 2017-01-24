<?php

use Laravel\Lumen\Testing\DatabaseTransactions;

use App\AvisoCliente;
use App\AvisoConfiguracion;
use App\Destinatario;

class EventoEngancheTest extends TestCase
{
    use DatabaseTransactions;

    public function testEngancheAviso()
    {
        $this->withoutEvents();
        $movil = $this->getMovil();
        $this->post('/eventos', [
            'evento_tipo_id' => self::ENGANCHE,
            'cliente_id' => $movil->cliente_id,
            'movil_id' => $movil->movil_id,
            'dominio' => 'ABC123',
            'timestamp' => 1485196076,
        ]);
        $this->assertResponseStatus(201);
    }

    public function testDesengancheAviso()
    {
        $this->withoutEvents();
        $movil = $this->getMovil();
        $this->post('/eventos', [
            'evento_tipo_id' => self::DESENGANCHE,
            'cliente_id' => $movil->cliente_id,
            'movil_id' => $movil->movil_id,
            'dominio' => 'ABC123',
            'timestamp' => 1485196096,
        ]);
        $this->assertResponseStatus(201);
    }
}
