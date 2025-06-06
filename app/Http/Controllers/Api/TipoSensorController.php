<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tipo_Sensor;
use App\Models\Sensor;

class TipoSensorController extends Controller
{
    /** Metodo para regresar los tipos de sensores en el sistema
     * @return \Illuminate\Http\JsonResponse Respuesta obtenida en formato JSON tanto mensaje de error como arreglo de registros */
    public function listaTipoSensores(){
        // Obtener todos los tipos de sensores de la BD
        $senTipos = Tipo_Sensor::all();

        // Regresar un error si no se encontraron tipos de sensores
        if($senTipos->isEmpty())
            return response()->json(['msgError' => 'Error: No hay tipos de sensores.'], 404);
        
        // Regresar la lista de tipos de sensores encontrados
        return response()->json(['results' => $senTipos], 200);
    }

    /** Metodo para regresar todos los sensores que no esten nombrados 
     * @return \Illuminate\Http\JsonResponse Respuesta obtenida en formato JSON tanto mensaje de error como arreglo de registros */
    public function listaSensoresNoRegi(){
        $listTipoID = Sensor::pluck('Tipo_ID');
        $listaSensores = Tipo_Sensor::whereNotIn('ID', $listTipoID)->select('ID', 'ID_')->get();

        // Regresar un error si no se encontraron sensores sin registrar
        if($listaSensores->isEmpty())
            return response()->json(['msgError' => 'Error: No se encontraron sensores en el sistema.'], 404);
        
        // Regresar la lista de sensores encontrados
        return response()->json(['results' => $listaSensores], 200);
    }
}
