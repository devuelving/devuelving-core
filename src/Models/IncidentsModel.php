<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentsModel extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'incidents';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code', 'type', 'status', 'order_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];
    
    /**
     * Returns a string based on the int it receives
     *
     * @param int $type
     * @return string
     */
    public function typeString($type)
    {
        $string="";
        switch ($type) {
            case 1:
                $string = "Rotura Parcial";
                break;
            case 2:
                $string = "Rotura Completa";
                break;
            case 3:
                $string = "Perdida de paquete parcial";
                break;
            case 4:
                $string = "Perdida de paquete completa";
                break;
            case 5:
                $string = "Pedido no contiene todos los productos";
                break;
            case 6:
                $string = "Pedido no entregado";
                break;
            case 7:
                $string = "No hay stock";
                break;
            default:
                $string = "Tipo de incidencia no especificado.";
        }
        return $string;
    }
    
    /**
     * Returns a string based on the int it receives
     *
     * @param int $status
     * @return string
     */
    public function statusString($status)
    {
        $string="";
        switch ($status) {
            case 1:
                $string = "Iniciada";
                break;
            case 2:
                $string = "Esperando respuesta";
                break;
            case 3:
                $string = "Tramitada";
                break;
            case 4:
                $string = "Cerrada";
                break;
            default:
                $string = "Estado de incidencia no especificado.";
        }
        return $string;
    }
}
