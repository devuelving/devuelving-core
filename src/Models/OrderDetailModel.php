<?php

namespace devuelving\core;

use devuelving\core\ProductModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDetailModel extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_details';

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
        'type', 'status', 'order', 'product', 'variation', 'units', 'units_prepared', 'unit_price', 'tax', 'tax_value', 'franchise_earning',
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

    /**
     *  Relationship product hasOne
     */
    public function productData()
    {
        return $this->hasOne('devuelving\core\ProductModel', 'id', 'product');
    }
    
    /**
     *  Relationship order box hasOne
     */
    public function orderBoxData()
    {
        return $this->hasOne('devuelving\core\OrderBoxActionModel', 'product', 'product')->where('order', $this->order);
    }

    /**
     *  Relationship product hasOne
     */
    public function productMainImage()
    {
        return null;//$this->hasOne('devuelving\core\ProductModel', 'id', 'product');
    }
}
