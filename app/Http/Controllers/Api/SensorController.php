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
        // Obtener la lista de sensores registrados y verificar si el sensor en cuestion ya existe en el sistema
        $sensoRegi = $this->listaSenRegi();
        if($sensoRegi){
        // Decodificar el json y recorrerlo en busqueda de algun registro previo
        $senDatos = json_decode($sensoRegi);
            foreach($senDatos->results as $sensor){
                if($sensor->ID_ == $consulta->identiNiag)
                    return response()->json(['msgError' => 'Error: El sensor a registrar ya existe en el sistema, favor de intentar con otro.'], 500);
            }
        }
        
        // Si no retornamos error en este punto, continuamos con el proceso, en este caso validar los campos
        $validador = Validator::make($consulta->all(), [
            'identiNiag' => 'required',
            'nombre' => 'required'
        ]);

        // Retornar error si el validador falla
        if($validador->fails())
            return response()->json(['msgError' => 'Error: Favor de ingresar la información requerida.'], 500);

        // Obtener el id del tipo de sensor registrado con el id de niagara
        $idTipoSen = Tipo_Sensor::where('ID_', '=', $consulta->identiNiag)->value('ID');

        // Regresar un error si el no se encontro el usuario
        if(!$idTipoSen)
            return response()->json(['msgError' => 'Error: Información no encontrada, la solicitud en cuestión no existe o ya fue realizada, favor de generar otra.'], 500);
    }
}
