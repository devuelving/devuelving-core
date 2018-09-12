<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class ProductStockModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_stock';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'type', 'provider', 'product', 'stock', 'purchase_price',
    ];
}
