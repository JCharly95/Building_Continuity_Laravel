<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use App\Models\Registro_Sensor;
use Illuminate\Database\Eloquent\Collection;

class ProcReduRegiHelper
{
    // NOTA: A diferencia de node, PHP es monohilo y si se desea trabajar con "hilos" en PHP se hariá con Jobs. El problema es que los jobs trabajan de forma independiente y debido a esto, no se puede capturar la información procesada de forma directa (como promesas con JS), para eso se usaria redis, guardar de forma temporal la información que se estuviera generando (seria como un sqlite en el navegador, algo asi entendi). En su lugar, se optó realizar la reducción mediante paginación, es decir, ir reduciendo la información evaluandola bloque a bloque, en lugar de todo a la vez.

    /** Función para analizar los datos de la consulta y determinar el proceso de reducción a realizar
     * @param \Illuminate\Http\Request $consulta Arreglo de valores con los elementos enviados desde el cliente
     * @param int $cantRegis Cantidad de registros obtenida desde la BD para la busqueda de registros
     * @return array Arreglo de valores resultante (menor al inicial) */
    public function anaReduProc(Request $consulta, int $cantRegis){
        // Crear el arreglo de resultados
        $arrResRedu = [];
        
        // Determinar la longitud de las muestras para el proceso de reducción
        $longiMuesRedu = ceil($cantRegis / 7500);
        
        // Determinar la longitud de los bloques de datos para cada (hilo) bloque de procesamiento
        $cantElemBloq = ceil($cantRegis / 4);
        
        // Ajustar los valores temporalmente de PHP para evitar el bloqueo de espera en la consulta: limite de memoria, tiempo máximo de ejecución de scripts (en segundos) y tiempo máximo para la obtención de información (en segundos); en este caso la BD. Ambos se establecerán a 7 minutos
        //ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 420);
        ini_set('max_input_time', 420);

        // Buscar y crear bloques de registros (paginar) el resultado de la consulta (usando chunk) sin guardar en el buffer para evitar el desborde de memoria. Este proceso seria el equivalente a la evaluación por hilos, ya que los bloques seran del tamaño de los hilos (1/4 de la consulta)
        Registro_Sensor::on('mariadb_unbuffered')->where([
            ['TIMESTAMP', '>=', ($consulta->fechIni * 1000)],
            ['TIMESTAMP', '<=', ($consulta->fechFin * 1000)],
            ['HISTORY_ID', '=', $consulta->senBus]
        ])->orderBy('TIMESTAMP')
        ->select(['TIMESTAMP', 'VALUE', 'STATUS_TAG'])
        ->chunk($cantElemBloq, function (Collection $bloqueHilo) use(&$arrResRedu, $longiMuesRedu){
            // Reiniciar las claves de cada bloque de datos para evitar el error de elementos asociativos
            // El error es que la primera iteraccion resulto en: [...] y las posteriores en: {ID: 9, [...]}
            $bloqueHilo = $bloqueHilo->values();
            
            // Llamar al metodo de reducción, transformar la colección de resultados a un arreglo y agregarlo al arreglo de resultados que se regresara al cliente. Se usa array_merge para agregar el resultado obtenido en el final del arreglo de valores ya obtenido
            $arrResRedu = array_merge($arrResRedu, $this->hiloAnaRedu($bloqueHilo, $longiMuesRedu)->toArray());
        });

        return $arrResRedu;
    }

    /** Metodo equivalente al proceso de reducción recursiva usando paginación, para reducir la cantidad de registros a mostrar
     * @param Illuminate\Database\Eloquent\Collection $colecARedu Colección o bloque de datos previamente seccionado como hilo a reducir
     * @param int $longiMues Longitud de los subarreglos a analizar; las muestras
     * @return Illuminate\Support\Collection Colección de valores filtrado resultante de menor longitud a la colección inicial */
    protected function hiloAnaRedu(Collection $colecARedu, int $longiMues){
        /** @var \Illuminate\Support\Collection<string|int, mixed> $colecReduRes Colección de registros que almacenará el resultado de la de reducción */
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
