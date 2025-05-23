<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sensor;
use Illuminate\Support\Facades\Validator;
use App\Models\Tipo_Sensor;

class SensorController extends Controller
{
    /** Metodo para regresar todos los sensores de la tabla */
    public function listaSensores(){
        // Obtener todos los sensores de la BD usando el modelo para buscarlos
        $sensores = Sensor::all();

        // Regresar un error si no se encontraron sensores
        if($sensores->isEmpty())
            return response()->json(['msgError' => 'Error: No hay sensores registrados.'], 404);

        // Regresar la lista de sensores encontrados
        return response()->json(['results' => $sensores], 200);
    }

    /** Metodo para regresar todos los sensores que estan registrados */
    public function listaSenRegi(){
        $listaSenRegi = Sensor::select('sensor.ID_Sensor' ,'sensor.Nombre', 'history_type_map.ID_', 'history_type_map.VALUEFACETS')->join('history_type_map', 'sensor.Tipo_ID', '=', 'history_type_map.ID')->get();

        // Regresar un error si no se encontraron los sensores
        if($listaSenRegi->isEmpty())
            return response()->json(['msgError' => 'Error: No se encontraron sensores registrados.'], 404);

        // Regresar la lista de sensores encontrados
        return response()->json(['results' => $listaSenRegi], 200);
    }

    /** Metodo para registrar un sensor */
    public function regiSensor(Request $consulta){
        // Obtener la lista de sensores registrados y verificar si el sensor en cuestión ya existe en el sistema
        $sensoRegi = $this->listaSenRegi();

        // Determinar si se obtuvo una respuesta no vacia
        if(!empty($sensoRegi->getContent())){
            // Decodificar el json y determinar que tipo de respuesta se obtuvo acorde a las propiedades de la respuesta
            $senDatos = json_decode($sensoRegi, true);
            // Si se obtuvo un error de busqueda se regresará un error de procesamiento del sistema
            if(array_key_exists('msgError', $senDatos))
                return response()->json(['msgError' => $senDatos['msgError']], 404);

            // Si no, se recorrera el resultado en busqueda de algún registro previo
            foreach($senDatos['results'] as $sensor){
                if($sensor['ID_'] == $consulta->identiNiag){
                    return response()->json(['msgError' => 'Error: El sensor a registrar ya existe en el sistema.'], 500);
                }
            }
        }
        
        // Si no se ha regresado error hasta este punto, continuamos con el proceso, en este caso validar los campos
        $validador = Validator::make($consulta->all(), [
            'identiNiag' => 'required',
            'nombre' => 'required'
        ]);

        // Retornar error si el validador falla
        if($validador->fails())
            return response()->json(['msgError' => 'Error: Favor de revisar la información ingresada.'], 500);

        // Obtener el id del tipo de sensor registrado con el id de niagara
        $idTipoSen = Tipo_Sensor::where('ID_', '=', $consulta->identiNiag)->value('ID');

        // Regresar un error porque no se encontró el id del tipo de sensor
        if(!$idTipoSen)
            return response()->json(['msgError' => 'Error: El sistema no pudo encontrar la información relacionada con la solicitud.'], 500);

        // Registrar el sensor en el sistema (Insert a la BD)
        $sensor = Sensor::create([
            'Nombre' => $consulta->nombre,
            'Tipo_ID' => $consulta->identiNiag
        ]);

        // Regresar un error si no se pudo registrar el sensor
        if(!$idTipoSen)
            return response()->json(['msgError' => 'Error: El sensor'.$consulta->nombre.' no pudo ser registrado. Favor de intentar nuevamente.'], 500);

        // Regresar el mensaje de consulta realizada
        return response()->json(['results' => 'El sensor'.$consulta->nombre.' fue registrado exitosamente.'], 200);
    }
}
