<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderHistory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order', 'status',
    ];

    public function statusName()
    {
        switch ($this->status) {
            case 1:
                return "Recibido";
                break;
            case 2:
                return "En preparación";
                break;
            case 3:
                return "Preparado";
                break;
            case 4:
                return "Pendiente de envío";
                break;
            case 5:
                return "Enviado";
                break;
            case 6:
                return "En transito";
                break;
            case 7:
                return "En reparto";
                break;
            case 8:
                return "Entregado";
                break;
            case 9:
                return "Incidencia";
                break;
        }
    }
}
