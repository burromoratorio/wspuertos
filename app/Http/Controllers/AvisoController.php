<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Aviso;

class AvisoController extends Controller
{
    const ESTADO_PENDIENTE = 1;
    const ESTADO_ENVIADO = 2;
    const ESTADO_FALLIDO = 3;

    use AvisoTrait;

    public function index()
    {
        return Aviso::join('estados_envios', 'estados_envios.id', '=', 'avisos.estado_envio_id')
                    ->join('avisos_clientes', 'avisos_clientes.id', '=', 'avisos.aviso_cliente_id')
                    ->join('clientes', 'clientes.cliente_id', '=', 'avisos_clientes.cliente_id')
                    ->select('avisos.id', 'avisos.aviso', 'estados_envios.estado', 'clientes.razon_social')
                    ->get();
    }

    public function update(Request $request, $id) {
        $aviso = Aviso::findOrFail($id);
        $estado = $request->input('estado_envio_id');
        if ($estado == static::ESTADO_PENDIENTE) {
            // TODO: terminar reintentos
            /*$aviso_id = $aviso->id;
            $subject = $aviso->subject;
            $body = $aviso->body;
            $addresses = ;
            Redis::publish('mails', json_encode(compact('aviso_id', 'subject', 'body', 'addresses')));*/
        }
        $aviso->estado_envio_id = $estado;
        $aviso->save();
        return "Update OK";
    }
}
