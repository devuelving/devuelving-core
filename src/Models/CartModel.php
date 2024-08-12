<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class CartModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cart';

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
        'customer', 'product', 'variation', 'units',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'created_at', 'updated_at',
    ];
    /**
     * Relationship order order_notes hasOne
     */
    public function productCart()
    {
        return $this->hasOne('devuelving\core\ProductModel', 'id', 'product');
    }
}
