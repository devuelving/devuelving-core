<?php

namespace devuelving\core;

use devuelving\core\ProductModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MyOrderDetailModel extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'my_shop_order_details';

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
        'type', 'status', 'order', 'product', 'name', 'variation', 'units', 'unit_price', 'tax', 'tax_value',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'created_at', 'updated_at', 'deleted_at',
    ];
    /**
     *  Relationship product hasOne
     */
    public function productData()
    {
        return $this->hasOne('devuelving\core\ProductModel', 'id', 'product');
    }
    /**
     * Función para obtener los detalles de un producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return Product
     */
    public function getProduct()
    {
        return ProductModel::find($this->product);
    }
}
