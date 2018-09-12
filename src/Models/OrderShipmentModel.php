<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class OrderShipmentModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_shipment';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order', 'status', 'shipping_company', 'shipping_tracking', 'shipping_notification'
    ];
}
