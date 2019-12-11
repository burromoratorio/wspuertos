<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Validator;
use App\Http\Requests;
use Illuminate\Http\Response;

use App\AvisoCliente;
use App\AvisoMovil;
use App\AvisoDestinatario;
use App\AvisoTipo;
use App\AvisoConfiguracion;
use App\AvisoConfiguracionTipo;
Use Log;
class AvisoClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return AvisoCliente::join('clientes', 'clientes.cliente_id', '=', 'avisos_clientes.cliente_id')
                           ->join('avisos_tipos', 'avisos_tipos.id', '=', 'avisos_clientes.aviso_tipo_id')
                           ->select('avisos_clientes.id', 'clientes.razon_social', 'avisos_tipos.tipo')
                           ->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       Log::error("no hay wps");

        $valid_aviso_cliente = Validator::make($request->all(), [
            'cliente_id'    => 'required|numeric',
            'aviso_tipo_id' => 'required|numeric',
        ]);

        if ($valid_aviso_cliente->fails()) {
            $response = [ 'message' => $valid_aviso_cliente->errors(), 'status' => 400 ];
            return response()->json(['error' => $response], $response['status']);
        } else {

            DB::beginTransaction();

            try {
                /* Alta avisoCliente */
                $aviso_cliente = new AvisoCliente;

                $aviso_cliente->cliente_id    = $request->get('cliente_id');
                $aviso_cliente->aviso_tipo_id = $request->get('aviso_tipo_id');

                $aviso_cliente->save();

                $this->setDataAvisoCliente( $aviso_cliente->id, $request );

                DB::commit();

                $response = $this->getResponseOK($aviso_cliente);

            } catch(\Exception $e) {
                DB::rollback();
                $response = [ 'message' => $e->getMessage(), 'status' => 400 ];
                return response()->json(['error' => $response], $response['status']);
            }

        }

        return response()->json( $response, $response['status']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $aviso_cliente = AvisoCliente::join('avisos_tipos as tipo', 'tipo.id', '=', 'avisos_clientes.aviso_tipo_id')
                                    ->select('avisos_clientes.id',
                                            'avisos_clientes.aviso_tipo_id',
                                            'avisos_clientes.cliente_id',
                                            'tipo.tipo')
                                    ->where('avisos_clientes.id', $id)
                                    ->get();

        if ( is_null($aviso_cliente) ) {
            $response = [ 'message' =>  'Registro no encontrado', 'status' => 404 ];
        } else {

            $response['id']            = $aviso_cliente[0]['id'];
            $response['aviso_tipo_id'] = $aviso_cliente[0]['aviso_tipo_id'];
            $response['cliente_id']    = $aviso_cliente[0]['cliente_id'];
            $response['aviso_tipo']    = $aviso_cliente[0]['tipo'];
            $response['status']        = 200;

            $configuraciones = AvisoConfiguracion::join('avisos_configuraciones_tipos as config_type',
                                                        'config_type.id', '=',
                                                        'avisos_configuraciones.aviso_configuracion_tipo_id')
                                                 ->select('avisos_configuraciones.id', 'avisos_configuraciones.valor',
                                                          'config_type.tipo as config_type')
                                                 ->where('avisos_configuraciones.aviso_cliente_id', $id)
                                                 ->get()
                                                 ->toArray();

            if ( count( $configuraciones ) > 0 ) $response['configuraciones'] = $configuraciones;

            $moviles = AvisoMovil::where('aviso_cliente_id', $id)->get()->toArray();

            if ( count( $moviles ) > 0 ) $response['moviles'] = $moviles;

            $destinatarios = AvisoDestinatario::select('destinatario_id')
                                              ->where('aviso_cliente_id', $id)
                                              ->get()
                                              ->toArray();

            if ( count( $destinatarios ) > 0 ) $response['destinatarios'] = $destinatarios;

        }

        return response()->json($response,  $response['status']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $aviso_cliente = AvisoCliente::find($id);

        if ( is_null($aviso_cliente) ) {
            $response = [ 'message' =>  'Registro no encontrado', 'status' => 404 ];
        } else {

            $validator = Validator::make($request->all(), [
                'cliente_id'    => 'required|numeric',
                'aviso_tipo_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                $response = [ 'message' => $validator->errors(), 'status' => 400 ];
                return response()->json(['error' => $response], $response['status']);
            } else {

                DB::beginTransaction();

                try {
                    /* Edición avisoCliente */
                    $input = $request->all();

                    $aviso_cliente->fill($input)->save();

                    // Elimino la información que este asignada al id
                    AvisoDestinatario::where('aviso_cliente_id', $aviso_cliente->id)->delete();
                    AvisoMovil::where('aviso_cliente_id', $aviso_cliente->id)->delete();
                    AvisoConfiguracion::where('aviso_cliente_id', $aviso_cliente->id)->delete();

                    // Agrego los nuevos datos
                    $this->setDataAvisoCliente( $aviso_cliente->id, $request );

                    DB::commit();

                    $response = $this->getResponseOK($aviso_cliente);

                } catch(\Exception $e) {
                    DB::rollback();
                    $response = [ 'message' => $e->getMessage(), 'status' => 400 ];
                    return response()->json(['error' => $response], $response['status']);
                }
            }
        }

        return response()->json( $response, $response['status']);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $aviso_cliente = AvisoCliente::find($id);

        if ( is_null($aviso_cliente) ) {
            $response = [ 'message' =>  'Registro no encontrado', 'status' => 404 ];
        } else {
            AvisoDestinatario::where('aviso_cliente_id', $aviso_cliente->id)->delete();
            AvisoMovil::where('aviso_cliente_id', $aviso_cliente->id)->delete();
            AvisoConfiguracion::where('aviso_cliente_id', $aviso_cliente->id)->delete();
            $aviso_cliente->delete();

            $response = $this->getResponseOK($aviso_cliente, 'delete');
        }

        return response()->json( $response, $response['status']);
    }

    /**
     * Setea destinatarios, unidades y configuraciones
     *
     * @param  int      $id
     * @param  request  $request
     *
     */
    private function setDataAvisoCliente( $id, Request $request )
    {
 /* Alta destinatarios de aviso */
        $destinatarios = $request->get('destinatarios');
        $this->setDestinatarios($id, $destinatarios);
        /* Alta de avisos unidades (si fueron enviadas) */
        $unidades = $request->get('unidades');
        if ( count( $unidades ) > 0 ) {
            $this->setUnidades($id, $unidades);
        }
        /* Alta de avisos configuraciones (si fueron enviadas) */
        if($waypoints = $request->get('waypoints')){
           $waypoints = $request->get('waypoints');
           $countWaypoints = count( $waypoints );
	   Log::error("llegando wp");
        }else{
           $countWaypoints = 0;
           Log::error("no hay wps");
        }
        $velocidad = $request->get('velocidad');
	if($velocidad!=''){
       	   $countVelocidad = count( $velocidad );
	}else{
	   Log::error("la velocity es vaciaee");
	}
        if ( $countWaypoints > 0 || $velocidad !== '' ) {

            $config_type = AvisoConfiguracionTipo::where('aviso_tipo_id', $request->get('aviso_tipo_id'))->first();

            //Velocidad
            if ( $velocidad !== '' ) {

                $avisoConfiguracion = new AvisoConfiguracion();

                $avisoConfiguracion->aviso_configuracion_tipo_id = $config_type->id;
                $avisoConfiguracion->aviso_cliente_id = $id;
                $avisoConfiguracion->valor = $velocidad;

                $avisoConfiguracion->save();
            }

            //Waypoints
            if ( $countWaypoints > 0 ) {
                $this->setConfiguraciones($id, $config_type->id, $waypoints);
            }
        }

    }

    private function setDestinatarios( $aviso_cliente_id, array $destinatarios )
    {
        foreach ($destinatarios as $key => $value) {
            $avisoDestinatario = new AvisoDestinatario();

            $avisoDestinatario->aviso_cliente_id = $aviso_cliente_id;
            $avisoDestinatario->destinatario_id  = $value;

            $avisoDestinatario->save();
        }
    }

    private function setUnidades( $aviso_cliente_id, array $unidades )
    {
        foreach ($unidades as $key => $value) {
            $avisoMovil = new AvisoMovil();

            $avisoMovil->aviso_cliente_id = $aviso_cliente_id;
            $avisoMovil->movil_id  = $value;

            $avisoMovil->save();
        }
    }

    private function setConfiguraciones( $aviso_cliente_id, $config_type, array $waypoints )
    {
        foreach ($waypoints as $key => $value) {
            $avisoConfiguracion = new AvisoConfiguracion();

            $avisoConfiguracion->aviso_configuracion_tipo_id = $config_type;
            $avisoConfiguracion->aviso_cliente_id = $aviso_cliente_id;
            $avisoConfiguracion->valor  = $value;

            $avisoConfiguracion->save();
        }
    }

    /**
     * Returns an array with the response
     *
     * @param  int      $aviso_cliente
     * @param  string   $action
     * @return array
     */
    private function getResponseOK($aviso_cliente, $action = 'new')
    {

        $response = ['id' => $aviso_cliente->id,
                     'cliente_id' => $aviso_cliente->cliente_id,
                     'aviso_tipo_id' => $aviso_cliente->aviso_tipo_id,
                     'message' => '',
                     'status' => 200
                    ];

        if ( $action == 'update' ) {
            $response['message'] = 'La regla ha sido actualizada satisfactoriamente';
        } elseif ( $action == 'delete' ) {
            $response['message'] = 'La regla ha sido eliminada satisfactoriamente';
        } else {
            $response['message'] = 'La regla ha sido creada satisfactoriamente';
        }

        return $response;
    }
}
