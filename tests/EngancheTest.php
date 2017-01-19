<?php

use Laravel\Lumen\Testing\DatabaseTransactions;

use App\AvisoCliente;
use App\AvisoConfiguracion;
use App\Destinatario;

class EngancheTest extends TestCase
{
    use DatabaseTransactions;

    public function testEngancheAviso()
    {
        $this->withoutEvents();
        $movil = $this->getMovil();
        $this->post('/eventos', [
            'evento_tipo_id' => self::ENGANCHE,
            'movil_id' => $movil->movil_id,
            'posicion_id' => 1,
            'cliente_id' => $movil->cliente_id,
            'dominio' => 'ABC123',
            'timestamp' => 123456,
        ]);
        $this->assertResponseStatus(201);
    }

    public function testDesngancheAviso()
    {
        $this->withoutEvents();
        $movil = $this->getMovil();
        $this->post('/eventos', [
            'evento_tipo_id' => self::DESENGANCHE,
            'movil_id' => $movil->movil_id,
            'posicion_id' => 1,
            'cliente_id' => $movil->cliente_id,
            'dominio' => 'ABC123',
            'timestamp' => 123456,
        ]);
        $this->assertResponseStatus(201);
    }
}
