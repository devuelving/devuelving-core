<?php

namespace devuelving\core;

use devuelving\core\ProductCustomModel;
use Illuminate\Database\Eloquent\Model;

class ProductCustomModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_custom';

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
        'product', 'franchise', 'name', 'description', 'promotion', 'price', 'price_type', 'meta_title', 'meta_description', 'meta_keywords',
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
     * Función para devolver el modelo de ProductCustom segun si ya hay un registro o no
     *
     * @param int $franchise
     * @param int $product
     * @return void
     */
    public static function get($franchise, $product)
    {
        $productCustom = ProductCustomModel::where('product', $product)->where('franchise', $franchise)->get();
        if (count($productCustom) == 0) {
            $productCustom = new ProductCustomModel();
            $productCustom->franchise = $franchise;
            $productCustom->product = $product;
            $productCustom->save();
            return ProductCustomModel::find($productCustom->id);
        } else {
            $productCustom = ProductCustomModel::where('product', $product)->where('franchise', $franchise)->first();
            return ProductCustomModel::find($productCustom->id);
        }
    }

    /**
     * Función para eliminar el registro de la base de datos, si no hay ningun elemento personalizado
     *
     * @param int $id
     * @return void
     */
    public static function checkClear($id)
    {
        $productCustom = ProductCustomModel::find($id);
        if ($productCustom->name == null && $productCustom->description == null && $productCustom->promotion == null && $productCustom->price == null) {
            $productCustom->delete();
        }
    }
}
