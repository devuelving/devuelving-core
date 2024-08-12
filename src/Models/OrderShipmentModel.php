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
        'order', 'type', 'franchise_cost', 'customer_cost', 'status', 'shipping_company', 'shipping_tracking', 'shipping_URL', 'shipping_notification', 'provider'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];
}
