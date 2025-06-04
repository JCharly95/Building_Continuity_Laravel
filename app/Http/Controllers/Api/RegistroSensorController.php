<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Registro_Sensor;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Collection;

class RegistroSensorController extends Controller{   
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

        if ($consulta->tipoConsul == 0) {
            $registros = Registro_Sensor::lazy();

            if ($registros->isEmpty())
                return response()->json(['msgError' => 'Error: No se encontró la información solicitada.'], 404);

            $results = [];
            foreach ($registros as $sensor) {
                $results[] = $sensor;

                // OPCIONAL: detener si solo quieres un máximo de N para no agotar memoria
                if (count($results) >= 10000) break;
            }

            return response()->json(['results' => $results], 200);
        } else {
            $registros = Registro_Sensor::take(10)->get();

            if ($registros->isEmpty())
                return response()->json(['msgError' => 'Error: No se encontró la información solicitada.'], 404);

            return response()->json(['results' => $registros], 200);
        }
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

        // Obtener la cantidad de registros que se encontraron en la BD
        $cantRegis = Registro_Sensor::on('mariadb_unbuffered')->where([
            ['TIMESTAMP', '>=', ($consulta->fechIni * 1000)],
            ['TIMESTAMP', '<=', ($consulta->fechFin * 1000)],
            ['HISTORY_ID', '=', $consulta->senBus]
        ])->count();

        // Si la cantidad de registros es menor a 15000 elementos, se regresará el result set de una vez
        if($cantRegis < 15000){
            $infoRes = Registro_Sensor::on('mariadb_unbuffered')->where([
                ['TIMESTAMP', '>=', ($consulta->fechIni * 1000)],
                ['TIMESTAMP', '<=', ($consulta->fechFin * 1000)],
                ['HISTORY_ID', '=', $consulta->senBus]
            ])->orderBy('TIMESTAMP')->get();

            return response()->json(['results' => $infoRes], 200);
        }

        // Si no, se realizará el proceso de reducción de valores
        // Paso 0: Ajustar los valores temporalmente de PHP para evitar el bloqueo de espera en la consulta: limite de memoria, tiempo maximo de ejecucion de scripts (en segundos) y tiempo maximo para la obtención de informacion (en segundos); en este caso la BD
        //ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);

        // Establecer el arreglo de resultados
        $arrResRedu = [];

        // Determinar la longitud de las muestras para el proceso de reducción
        $longiMuesRedu = ceil($cantRegis / 7500);

        // Determinar la longitud de los bloques de datos para cada "hilo" bloque de procesamiento de informacion
        $cantElemBloq = ceil($cantRegis / 4);

        //Buscar y crear bloques de registro (paginar) el resultado de la consulta (usando chunk)
        Registro_Sensor::on('mariadb_unbuffered')->where([
            ['TIMESTAMP', '>=', ($consulta->fechIni * 1000)],
            ['TIMESTAMP', '<=', ($consulta->fechFin * 1000)],
            ['HISTORY_ID', '=', $consulta->senBus]
        ])->orderBy('TIMESTAMP')
        ->chunk($cantElemBloq, function (Collection $bloqueHilo) use(&$arrResRedu, $longiMuesRedu){
            // Reiniciar las claves de cada bloque de datos para evitar el error de elementos asociativos
            $bloqueHilo = $bloqueHilo->values();
            
            // Invocar la funcion de creacion del hilo y agregar el resultado al arreglo de resultados
            $arrResRedu = array_merge($arrResRedu, $this->hiloAnaRedu($bloqueHilo, $longiMuesRedu)->toArray());
        });

        var_dump($arrResRedu);
        // Determinar si el arreglo de resultados tiene valores (es decir, si se reducion satisfactoriamente)
        if (count($arrResRedu) <= 0)
            return response()->json(['msgError' => 'Error: No se encontró la información solicitada.'], 404);

        return response()->json(['results' => $arrResRedu], 200);
    }

    /** Metodo equivalente al proceso de reduccion recursiva pero con paginación, para reducir la cantidad de registros a mostrar
     * @param Illuminate\Database\Eloquent\Collection $colecARedu Colección o bloque de datos previamente seccionado como hilo a reducir
     * @param int $longiMues Longitud de los subarreglos a analizar; las muestras
     * @return Illuminate\Support\Collection Colección de valores filtrado resultante de menor longitud a la colección inicial */
    protected function hiloAnaRedu(Collection $colecARedu, int $longiMues){
        // Crear la colección de resultados
        $colecReduRes = collect();

        // Fraccionar la colección de registros acorde a la longitud ingresada y reiniciar las claves
        $colecMuest = $colecARedu->chunk($longiMues);
        $colecMuest = $colecMuest->values();
        
        // Recorrer el arreglo de valores para obtener los resultados correspondientes de cada subarreglo(seccion particionada)
        foreach($colecMuest as $muestra){
            // Obtener los valores minimo y maximo de la muestra
            $valMinMues = $muestra->min('VALUE');
            $valMaxMues = $muestra->max('VALUE');

            /* Obtener todos los registros que coincidan con los valores obtenidos anteriormente y segun la cantidad de registros obtenidos, se agregan en la coleccion de resultados
            $arrRegiMin = $muestra->where('VALUE', "=", $valMinMues)->get();
            $arrRegiMax = $muestra->where('VALUE', "=", $valMaxMues)->get();

            /*if($arrRegiMin->count() > 1)
                foreach($arrRegiMin as $regiMin){
                    $colecReduRes->push($regiMin);
                }
            else
                $colecReduRes->push($arrRegiMin);

            if($arrRegiMax->count() > 1)
                foreach($arrRegiMax as $regiMax){
                    $colecReduRes->push($regiMax);
                }
            else
                $colecReduRes->push($arrRegiMax);

            // Ordenar la coleccion en base a la columna de la fecha y reemplazar el resultado
            $colecReduRes = $colecReduRes->sortBy('TIMESTAMP', SORT_NUMERIC);*/

            // Obtener los primeros registros que coincidan con el valor minimo y maximo de la muestra
            $regiMin = $muestra->firstWhere('VALUE', "=", $valMinMues);
            $regiMax = $muestra->firstWhere('VALUE', "=", $valMaxMues);

            // Agregar los registros obtenidos en la colección de resultados a regresar
            $colecReduRes->push($regiMin, $regiMax);
            // Ordenar los elementos de la colección en base al campo de la fecha
            $colecReduRes = $colecReduRes->sortBy('TIMESTAMP', SORT_NUMERIC);
        }
        return $colecReduRes;
    }
}
