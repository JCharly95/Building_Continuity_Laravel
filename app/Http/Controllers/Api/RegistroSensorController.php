<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Registro_Sensor;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Jobs\ReducRegiSensJob;

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

            if ($registros->isEmpty()) {
                return response()->json(['msgError' => 'Error: No se encontró la información solicitada.'], 404);
            }

            $results = [];
            foreach ($registros as $sensor) {
                $results[] = $sensor;

                // OPCIONAL: detener si solo quieres un máximo de N para no agotar memoria
                if (count($results) >= 10000) break;
            }

            return response()->json(['results' => $results], 200);
        } else {
            $registros = Registro_Sensor::take(10)->get();

            if ($registros->isEmpty()) {
                return response()->json(['msgError' => 'Error: No se encontró la información solicitada.'], 404);
            }

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
        // Paso 0: Ajustar los valores temporalmente de PHP para evitar el bloqueo de espera en la consulta: limite de memoria, tiempo maximo de ejecucion de scripts y tiempo maximo para la obtención de informacion; en este caso la BD
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
            ['TIMESTAMP', '>', ($consulta->fechIni * 1000)],
            ['TIMESTAMP', '<', ($consulta->fechFin * 1000)],
            ['HISTORY_ID', '=', $consulta->senBus]
        ])->orderBy('TIMESTAMP')
        ->chunk($cantElemBloq, function (Collection $bloqueHilo) use(&$arrResRedu, $longiMuesRedu){
            // Reiniciar las claves de cada bloque de datos para evitar el error de elementos asociativos
            $bloqueHilo = $bloqueHilo->values();
            
            // Invocar la funcion de creacion del hilo y agregar el resultado al arreglo de resultados
            $arrResRedu = array_merge($arrResRedu, $this->hiloAnaRedu($bloqueHilo, $longiMuesRedu));
        });

        /* Obtener todos los registros necesarios desde la BD
        $infoRes = Registro_Sensor::on('mariadb_unbuffered')->where([
            ['TIMESTAMP', '>=', ($consulta->fechIni * 1000)],
            ['TIMESTAMP', '<=', ($consulta->fechFin * 1000)],
            ['HISTORY_ID', '=', $consulta->senBus]
        ])->orderBy('TIMESTAMP')->get();*/

        /*
            Perfecto, usar Model::chunk() directamente es una gran decisión para rendimiento. Y para evitar el problema de claves asociativas en los chunks, solo necesitas agregar ->values() dentro del loop para que Laravel reinicie las claves de cada chunk antes de procesarlo.

            use App\Models\Model;

            $arrReducRes = [];
            $tamArrMues = 100; // o el valor que necesites

            Model::where(...) // tus condiciones aquí
            ->chunk(1000, function ($arrInfoChunk) use ($tamArrMues, &$arrReducRes) {
                // ⚠️ Resetear claves numéricas del chunk para evitar problemas
                $arrInfoChunk = $arrInfoChunk->values();

                // Procesar chunk
                $res = anaProcReduc($arrInfoChunk, $tamArrMues);

                // Acumular resultados
                $arrReducRes = array_merge($arrReducRes, $res);
            });

            ¿Y anaProcReduc() cómo debe quedar?
            Sigue igual, pero asegúrate de que $arrInfo sea una colección indexada con claves numéricas, como ya garantizaste arriba con ->values():

            function anaProcReduc(Collection $arrInfo, int $tamArrMues): array {
                $cantHilos = 4;
                $arrReducRes = [];

                $arrInfoChunks = $arrInfo->chunk(ceil($arrInfo->count() / $cantHilos));

                foreach ($arrInfoChunks as $arrHiloInfo) {
                    $res = genHilo($arrHiloInfo, $tamArrMues);
                    foreach ($res as $r) {
                        $arrReducRes[] = $r;
                    }
                }

                return $arrReducRes;
            }

        */
        
        //echo "Resultado consulta MySQL sin paginar: ".$infoRes."\n";

        //$this->anaProcReduc($infoRes, $longiMuesRedu);

        /* Buscar y crear bloques de registro (paginar) el resultado de la consulta (usando chunk)
        Registro_Sensor::on('mariadb_unbuffered')->where([
            ['TIMESTAMP', '>', ($consulta->fechIni * 1000)],
            ['TIMESTAMP', '<', ($consulta->fechFin * 1000)],
            ['HISTORY_ID', '=', $consulta->senBus]
        ])->orderBy('TIMESTAMP')
        ->chunk($cantElemBloq, function (Collection $bloqueHilo) use($longiMuesRedu){
            $colecDatosHilo = $bloqueHilo->chunk($longiMuesRedu);

            foreach($colecDatosHilo as $regisHilo){
                echo "Bloque de registros: ".$regisHilo."\n";
            }
        });*/
        
        /* Buscar y obtener el conjunto de registros en la BD. NOTA: Se usará la conexión de consultas sin uso de buffer para no guardar la información en el buffer y generar un desborde de memoria
        $infoRes = Registro_Sensor::on('mariadb_unbuffered')->where([
            ['TIMESTAMP', '>', ($consulta->fechIni * 1000)],
            ['TIMESTAMP', '<', ($consulta->fechFin * 1000)],
            ['HISTORY_ID', '=', $consulta->senBus]
        ])->orderBy('TIMESTAMP')->cursor();*/

        /* Paso 1: Calcular el tamaño de particiones para cada bloque (seria el equivalente de paginacion para cada hilo)
        $cantElemBloq = $cantRegis / 4;

        // Paso 2: Buscar y crear bloques de registro (paginar) el resultado de la consulta (usando chunk)
        Registro_Sensor::on('mariadb_unbuffered')->where([
            ['TIMESTAMP', '>', ($consulta->fechIni * 1000)],
            ['TIMESTAMP', '<', ($consulta->fechFin * 1000)],
            ['HISTORY_ID', '=', $consulta->senBus]
        ])->orderBy('TIMESTAMP')
        ->chunk($cantElemBloq, function (Collection $bloqueRegis) use(&$arrResRedu){
            // Paso 3: Calcular el tamaño de las muestras de cada bloque (hilo) de analisis
            $longiMues = ceil($bloqueRegis->count() / 7500);

            echo ("Cantidad de las muestras: ".$bloqueRegis->count()."\n");
            echo ("Longitud de las muestras: ".$longiMues."\n");
            
            // Paso 4: Seccionando los registros en muestras mas chicas (obtención de las muestras)
            $arrMues = $bloqueRegis->chunk($longiMues);

            echo $arrMues;

            // Paso 5: Obtencion de los valores minimo y maximo de cada muestra, con posterior adicion al arreglo resultante
            foreach($arrMues as $muestra){
                // Paso 6: Obtener los valores minimo y maximo de la muestra
                $valMinMues = $muestra->min('VALUE');
                $valMaxMues = $muestra->max('VALUE');

                // Paso 7: Obtener los registros que contengan los valores minimo y maximo
                $regiMin = $muestra->where('VALUE', "=", $valMinMues);
                $regiMax = $muestra->where('VALUE', "=", $valMaxMues);
                
                // Agregar los registros al arreglo de valores
                // array_push($arrResRedu, $regiMin, $regiMax);
                array_push($arrResRedu, $valMinMues, $valMaxMues);
            }
        });*/

        /*Registro_Sensor::on('mariadb_unbuffered')->where([
            ['TIMESTAMP', '>', ($consulta->fechIni * 1000)],
            ['TIMESTAMP', '<', ($consulta->fechFin * 1000)],
            ['HISTORY_ID', '=', $consulta->senBus]
        ])->orderBy('TIMESTAMP')
        ->chunk(7500, function (Collection $bloqueRegis) use(&$arrResRedu){
            $longiMues = ceil($bloqueRegis->count() / 7500);
            $muestra = $bloqueRegis->chunk($longiMues);

            foreach($muestra as $registro){
                $valMinMues = $muestra->min('VALUE');
                $valMaxMues = $muestra->max('VALUE');

                $regiMin = $registro->where('VALUE', "=", $valMinMues);
                $regiMax = $registro->where('VALUE', "=", $valMaxMues);
                
                array_push($arrResRedu, $regiMin, $regiMax);
            }
        });*/
        //print_r("Arreglo de valores reducido: ".$arrResRedu);

        // Determinar si el arreglo de resultados tiene valores (es decir, si se reducion satisfactoriamente)
        if (count($arrResRedu) <= 0) {
            return response()->json(['msgError' => 'Error: No se encontró la información solicitada.'], 404);
        }

        return response()->json(['results' => $arrResRedu], 200);
    }

    /** Metodo equivalente al proceso de reduccion recursiva pero con paginación, para reducir la cantidad de registros a mostrar
     * @param Illuminate\Database\Eloquent\Collection $colecARedu Colección o bloque de datos previamente seccionado como hilo a reducir
     * @param int $longiMues Longitud de los subarreglos a analizar; las muestras
     * @return array Arreglo de valores filtrado resultante de menor longitud a la colección inicial */
    protected function hiloAnaRedu(Collection $colecARedu, int $longiMues){
        // Crear el arreglo de resultados
        $arrReduRes = [];

        // Fraccionar el arreglo de registros acorde a la longitud ingresada
        $colecMuest = $colecARedu->chunk($longiMues);
        
        // Recorrer el arreglo de valores para obtener los resultados correspondientes de cada subarreglo(seccion particionada)
        foreach($colecMuest as $muestra){
            // Obtener los valores minimo y maximo de la muestra
            $valMinMues = $muestra->min('VALUE');
            $valMaxMues = $muestra->max('VALUE');

            // Obtener todos los registros que coincidan con los valores obtenidos anteriormente
            //$regiMin = $muestra->where('VALUE', "=", $valMinMues)->orderBy('TIMESTAMP')->get();
            //$regiMax = $muestra->where('VALUE', "=", $valMaxMues)->orderBy('TIMESTAMP')->get();
            $regiMin = $muestra->firstWhere('VALUE', "=", $valMinMues);
            $regiMax = $muestra->firstWhere('VALUE', "=", $valMaxMues);

            // Los registros obtenidos, son colecciones de eloquent, asi que se convierten a arreglos y se agregan al arreglo de resultados a entregar
            array_push($arrReduRes, $regiMin->toArray(), $regiMax->toArray());
        }
        return $arrReduRes;
    }
}
