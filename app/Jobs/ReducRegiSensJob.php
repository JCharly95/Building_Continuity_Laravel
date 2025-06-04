<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Registro_Sensor;

class ReducRegiSensJob implements ShouldQueue
{
    use Queueable;
    protected $datosCliente;
    protected $colecDatos;

    /**
     * Create a new job instance.
     */
    public function __construct(Request $datosConsul){
        $this->datosCliente = $datosConsul;
    }

    /**
     * Execute the job.
     */
    public function handle(){
        // Determinar la cantidad de elementos que contiene la consulta
        if($this->colecDatos->count() < 15000){

        }
    }
}
