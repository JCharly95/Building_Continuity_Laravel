<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tipo_Sensor extends Model
{
    /** Tabla asociada al modelo
     * @var string */
    protected $table = "history_type_map";

    /** Clave primaria de la tabla
     * @var string */
    protected $primaryKey = "ID";

    /** Variable de timestamps para la tabla
     * @var bool */
    public $timestamps = false;

    /** Atributos visibles y asignados en consultas masivas 
     * @var list<string> */
    protected $fillable = [
        'ID_',
        'TIMEZONE',
        'RECORDTYPE',
        'VALUEFACETS',
        'TABLE_NAME',
        'DB_TIMEZONE'
    ];
}