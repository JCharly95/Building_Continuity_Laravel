<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Registro_Sensor;
use Illuminate\Support\Facades\Validator;

class RegistroSensorController extends Controller
{
    
    /* Ejemplo de consulta con filtrado de datos usando Eloquent (ORM de Laravel):
    $flights = Flight::where('active', 1)
        ->orderBy('name')
        ->take(10)
        ->get();
    Nota para la elaboracion de la consultas:
    Se debe usar get() al final si se busca obtener multiples registros y first() en caso de requerir un solo registro. */

    /** Metodo para regresar los primeros 10 registros de sensores en el sistema */
    public function listaRegistroSensores(Request $consulta){
        $registros = null;
        // Determinar el tipo de consulta a usar
        if($consulta->tipoConsul == 0){
            // Obtener todos los registros tomando su tiempo
            $registros = Registro_Sensor::lazy();
        } else {
            // Obtener los primeros 10 registros de los sensores
            $registros = Registro_Sensor::all()->take(10);
        }

        // Regresar un error si no se encontraron registros
        if($registros->isEmpty() || is_null($registros)){
            return response()->json(['msgError' => 'Error: No se encontró la información solicitada.'], 404);
        }
        // Regresar la lista de registros
        return response()->json(['results' => $registros], 200);
    }

    /** Metodo para regresar los registros especificos acorde a una busqueda */
    public function listaRegistroEspeci(Request $consulta){
        // Validar la información enviada desde el cliente
        $validador = Validator::make($consulta->all(), [
            'senBus' => 'required',
            'fechIni' => 'required',
            'fechFin' => 'required'
        ]);

        // Retornar error si el validador falla
        if($validador->fails())
            return response()->json(['msgError' => 'Error: La información solicitada no fue enviada. Favor de intentar nuevamente.'], 500);

        // Buscar y obtener el usuario en la BD
        $infoRes = Registro_Sensor::where([
            ['TIMESTAMP', '>', ($consulta->fechIni * 1000)],
            ['TIMESTAMP', '<', ($consulta->fechFin * 1000)],
            ['HISTORY_ID', '=', $consulta->senBus]
        ])->orderBy('TIMESTAMP')->get();

        // Aqui se agregará el codigo donde se implementara la reduccion de valores
        // Se contempla usar Lazy y Jobs para asemejar la reduccion planteada con hilos en JS
    }
}
