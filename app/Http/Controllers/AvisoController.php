<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Aviso;

class AvisoController extends Controller
{
    use AvisoTrait;

    public function index()
    {
        /*return Aviso::join('estados_envios', 'estados_envios.id', '=', 'avisos.estado_envio_id')
                    ->join('avisos_clientes', 'avisos_clientes.id', '=', 'avisos.aviso_cliente_id')
                    ->join('clientes', 'clientes.cliente_id', '=', 'avisos_clientes.cliente_id')
                    ->select('avisos.id', 'avisos.aviso', 'estados_envios.estado', 'clientes.razon_social')
                    ->get();*/
        $avisos	= Aviso::getAvisos();
        $av		= array();
        foreach ($avisos as $aviso){
			$avi	= ['id'=>$aviso->id,'aviso'=>$aviso->aviso,'estado'=>$aviso->estado->estado,'razon_social'=>$aviso->avisoCliente->cliente->razon_social];
			$av[]	= $avi;

		}
		$header	= ['Content-type'=> 'application/json; charset=utf-8'];
        return response()->json($av,200,$header,JSON_UNESCAPED_UNICODE);          
       
    }

    public function update(Request $request, $id) {
        $this->validate($request, [
            'estado_envio_id' => 'required|numeric',
        ]);

        $aviso = Aviso::findOrFail($id);
        $estado = $request->input('estado_envio_id');
        if ($estado == self::$ESTADO_PENDIENTE) {
            list($subject, $body) = explode(";", $aviso->aviso);
            $this->fireEvent($subject, $body, $aviso->aviso_cliente_id, $aviso->id);
        }
        $aviso->estado_envio_id = $estado;
        $aviso->save();
        return "Update OK";
    }
}
