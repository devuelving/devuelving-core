<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class ShippingFee extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shipping_fees';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', ' name', 'rate_2', 'rate_3', 'rate_5', 'rate_7', 'rate_10', 'rate_15', 'rate_20', 'rate_30', 'rate_40', 'shipping_company', 'shipping_notifications', 'shipping_notifications_price',
    ];
}
