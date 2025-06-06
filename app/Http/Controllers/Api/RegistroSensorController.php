<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Registro_Sensor;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ProcReduRegiHelper;

class RegistroSensorController extends Controller
{   
    /* Ejemplo de consulta con filtrado de datos usando Eloquent (ORM de Laravel):
    $flights = Flight::where('active', 1)
        ->orderBy('name')
        ->take(10)
        ->get();
    Nota para la elaboracion de la consultas:
    Se debe usar get() al final si se busca obtener multiples registros y first() en caso de requerir un solo registro. */

    /** Metodo para regresar los primeros 10 registros de sensores en el sistema 
     * @param \Illuminate\Http\Request $consulta Arreglo de valores con los elementos enviados desde el cliente
     * @return \Illuminate\Http\JsonResponse Respuesta obtenida en formato JSON tanto mensaje de error como arreglo de registros */
    public function listaRegistroSensores(Request $consulta){
        // Establecer la variable que almacenará el arreglo de registros
        $registros = null;

        // Determinar si se solicito una consulta "completa" de registro o una limitada
        if ($consulta->tipoConsul == 0) {
            // Crear el arreglo de registros
            $results = [];
            // Obtener todos los registros de forma escalonada
            $registros = Registro_Sensor::lazy();

            if ($registros->isEmpty())
                return response()->json(['msgError' => 'Error: No se encontró la información solicitada.'], 404);

            // Recorrer la coleccion de registros consultada e irlos agregando en el arreglo de resultados a regresar
            foreach ($registros as $sensor) {
                $results[] = $sensor;
                // OPCIONAL: detener si solo quieres un máximo de N para no agotar memoria; en este caso 10'000 registros
                if (count($results) >= 10000) break;
            }

            return response()->json(['results' => $results], 200);
        } else {
            // Obtener los primeros 10 registros almacenados
            $registros = Registro_Sensor::take(10)->get();

            if ($registros->isEmpty())
                return response()->json(['msgError' => 'Error: No se encontró la información solicitada.'], 404);

            return response()->json(['results' => $registros], 200);
        }
    }

    /** Metodo para regresar los registros especificos acorde a una busqueda 
     * @param \Illuminate\Http\Request $consulta Arreglo de valores con los elementos enviados desde el cliente
     * @return \Illuminate\Http\JsonResponse Respuesta obtenida en formato JSON tanto mensaje de error como arreglo de registros */
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

        // Obtener la cantidad de registros que se encontraron en la BD
        $cantRegis = Registro_Sensor::on('mariadb_unbuffered')->where([
            ['TIMESTAMP', '>=', ($consulta->fechIni * 1000)],
            ['TIMESTAMP', '<=', ($consulta->fechFin * 1000)],
            ['HISTORY_ID', '=', $consulta->senBus]
        ])->count();

        // Si la cantidad de registros es menor a 15000 elementos, se regresará el bloque de registros sin reducir
        if($cantRegis <= 15000){
            $infoRes = Registro_Sensor::on('mariadb_unbuffered')->where([
                ['TIMESTAMP', '>=', ($consulta->fechIni * 1000)],
                ['TIMESTAMP', '<=', ($consulta->fechFin * 1000)],
                ['HISTORY_ID', '=', $consulta->senBus]
            ])->orderBy('TIMESTAMP')
            ->select(['TIMESTAMP', 'VALUE', 'STATUS_TAG'])->get();

            return response()->json(['results' => $infoRes], 200);
        }

        // Si no, se realizará el proceso de reducción de valores.
        // Crear el objeto helper y llamar el proceso de reducción de valores y almacenarlo en un arreglo
        $arrResRedu = app(ProcReduRegiHelper::class)->anaReduProc($consulta, $cantRegis);
        
        // Si el arreglo de resultados no tiene valores, se regresará un error
        if (count($arrResRedu) <= 0)
            return response()->json(['msgError' => 'Error: No se encontró la información solicitada.'], 404);

        return response()->json(['results' => $arrResRedu], 200);
    }
}
