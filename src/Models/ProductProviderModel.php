<?php

namespace devuelving\core;

use devuelving\core\ProductModel;
use devuelving\core\ProviderModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductProviderModel extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_provider';

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
        'product', 'ean', 'reference', 'cost_price', 'provider', 'stock',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        self::created(function ($productProvider) {
            $product = ProductModel::find($productProvider->product);
            $product->updatePrice();
        });

        self::updated(function ($productProvider) {
            $product = ProductModel::find($productProvider->product);
            $product->updatePrice();
        });
    }

    /**
     * Obtiene el proveedor
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getProvider()
    {
        return ProviderModel::find($this->provider);
    }
}
