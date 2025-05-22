<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registro_Sensor extends Model
{
    /** Tabla asociada al modelo
     * @var string */
    protected $table = "historynumerictrendrecord";

    /** Clave primaria de la tabla
     * @var string */
    protected $primaryKey = "ID";

    /** Variable de timestamps para la tabla
     * @var bool */
    public $timestamps = false;
    
    /** Atributos visibles y asignados en consultas masivas
     * @var list<string> */
    protected $fillable = [
        'TIMESTAMP',
        'TRENDFLAGS',
        'STATUS',
        'VALUE',
        'HISTORY_ID',
        'TRENDFLAGS_TAG',
        'STATUS_TAG'
    ];
}
