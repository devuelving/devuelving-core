<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderHistoryModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_history';

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
        'order', 'status', 'comments', 'agent',
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
     * Listado de los estados posibles del pedido
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function statusName()
    {
        switch ($this->status) {
            case 0:
                return __("Sin finalizar");
                break;
            case 1:
                return __("Pendiente de pago");
                break;
            case 2:
                return __("Pagado");
                break;
            case 3:
                return __("En preparación");
                break;
            case 4:
                return __("Preparado");
                break;
            case 5:
                return __("Enviado");
                break;
            case 6:
                return __("En transito");
                break;
            case 7:
                return __("En reparto");
                break;
            case 8:
                return __("Entregado");
                break;
            case 9:
                return __("Devuelto");
                break;
            case 10:
                return __("Cancelado");
                break;
            case 11:
                return __("Incidencia");
                break;
        }
    }
}
