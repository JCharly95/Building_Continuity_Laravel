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

        // Si la cantidad de registros es menor a 15000 elementos, se regresará el bloque de registros sin reducir
        if($cantRegis <= 15000){
            $infoRes = Registro_Sensor::on('mariadb_unbuffered')->where([
                ['TIMESTAMP', '>=', ($consulta->fechIni * 1000)],
                ['TIMESTAMP', '<=', ($consulta->fechFin * 1000)],
                ['HISTORY_ID', '=', $consulta->senBus]
            ])->orderBy('TIMESTAMP')
            ->get(['TIMESTAMP', 'VALUE', 'STATUS_TAG']);

            return response()->json(['results' => $infoRes], 200);
        }

        // Si no, se realizará el proceso de reducción de valores.
        // NOTA: A diferencia de node, PHP es monohilo y si se desea trabajar con "hilos" en PHP se hariá con Jobs. El problema es que los jobs trabajan de forma independiente y debido a esto, no se puede capturar la información procesada de forma directa (como promesas con JS), para eso se usaria redis, guardar de forma temporal la información que se estuviera generando (seria como un sqlite en el navegador, algo asi entendi). En su lugar, se optó realizar la reducción mediante paginación, es decir, ir reduciendo la información evaluandola bloque a bloque, en lugar de todo a la vez.

        // Paso 0: Ajustar los valores temporalmente de PHP para evitar el bloqueo de espera en la consulta: limite de memoria, tiempo máximo de ejecución de scripts (en segundos) y tiempo máximo para la obtención de información (en segundos); en este caso la BD. Ambos se establecerán a 7 minutos
        //ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 420);
        ini_set('max_input_time', 420);

        // Paso 1: Crear el arreglo de resultados
        $arrResRedu = [];

        // Paso 2: Determinar la longitud de las muestras para el proceso de reducción
        $longiMuesRedu = ceil($cantRegis / 7500);

        // Paso 3: Determinar la longitud de los bloques de datos para cada (hilo) bloque de procesamiento
        $cantElemBloq = ceil($cantRegis / 4);

        // Paso 4: Buscar y crear bloques de registros (paginar) el resultado de la consulta (usando chunk) sin guardar en el buffer para evitar el desborde de memoria. Este proceso seria el equivalente a la evaluación por hilos, ya que los bloques seran del tamaño de los hilos (1/4 de la consulta)
        Registro_Sensor::on('mariadb_unbuffered')->where([
            ['TIMESTAMP', '>=', ($consulta->fechIni * 1000)],
            ['TIMESTAMP', '<=', ($consulta->fechFin * 1000)],
            ['HISTORY_ID', '=', $consulta->senBus]
        ])->orderBy('TIMESTAMP')
        ->select(['TIMESTAMP', 'VALUE', 'STATUS_TAG'])
        ->chunk($cantElemBloq, function (Collection $bloqueHilo) use(&$arrResRedu, $longiMuesRedu){
            // Paso 5: Reiniciar las claves de cada bloque de datos para evitar el error de elementos asociativos
            // El error es que la primera iteraccion resulto en: [...] y las posteriores en: {ID: 9, [...]}
            $bloqueHilo = $bloqueHilo->values();
            
            // Paso 6: Llamar al metodo de reducción, transformar la colección de resultados a un arreglo y agregarlo al arreglo de resultados que se regresara al cliente. Se usa array_merge para agregar el resultado obtenido en el final del arreglo de valores ya obtenido
            $arrResRedu = array_merge($arrResRedu, $this->hiloAnaRedu($bloqueHilo, $longiMuesRedu)->toArray());
        });

        // Si el arreglo de resultados no tiene valores, se regresará un error
        if (count($arrResRedu) <= 0)
            return response()->json(['msgError' => 'Error: No se encontró la información solicitada.'], 404);

        return response()->json(['results' => $arrResRedu], 200);
    }

    /** Metodo equivalente al proceso de reduccion recursiva pero con paginación, para reducir la cantidad de registros a mostrar
     * @param Illuminate\Database\Eloquent\Collection $colecARedu Colección o bloque de datos previamente seccionado como hilo a reducir
     * @param int $longiMues Longitud de los subarreglos a analizar; las muestras
     * @return Illuminate\Support\Collection Colección de valores filtrado resultante de menor longitud a la colección inicial */
    protected function hiloAnaRedu(Collection $colecARedu, int $longiMues){
        /** @var \Illuminate\Support\Collection<string|int, mixed> $colecReduRes Colección de registros que almacenará el resultado de la de reducción*/
        $colecReduRes = collect();

        // Fraccionar la colección de registros acorde a la longitud ingresada y reiniciar las claves para evitar el error de elementos asociativos
        $colecMuest = $colecARedu->chunk($longiMues);
        $colecMuest = $colecMuest->values();
        
        // Recorrer el arreglo de valores para obtener los registros correspondientes de cada subarreglo(seccion particionada)
        foreach($colecMuest as $muestra){
            // Obtener los valores minimo y maximo de la muestra
            $valMinMues = $muestra->min('VALUE');
            $valMaxMues = $muestra->max('VALUE');

            // Obtener los primeros registros que coincidan con el valor minimo y maximo de la muestra
            $regiMin = $muestra->firstWhere('VALUE', "=", $valMinMues);
            $regiMax = $muestra->firstWhere('VALUE', "=", $valMaxMues);

            // Agregar los registros obtenidos en la colección de resultados a regresar
            $colecReduRes->push($regiMin, $regiMax);

            // Ordenar los elementos de la colección en base al campo de la fecha y haciendo comparacion numerica (la bandera de sort implementada)
            $colecReduRes = $colecReduRes->sortBy('TIMESTAMP', SORT_NUMERIC);
        }
        return $colecReduRes;
    }
}
