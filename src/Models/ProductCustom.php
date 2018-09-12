<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class ProductCustom extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_custom';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product', 'franchise', 'name', 'description', 'promotion', 'price', 'price_type',
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
        $productCustom = ProductCustom::where('product', $product)->where('franchise', $franchise)->get();
        if (count($productCustom) == 0) {
            $productCustom = new ProductCustom();
            $productCustom->franchise = $franchise;
            $productCustom->product = $product;
            $productCustom->save();
            return ProductCustom::find($productCustom->id);
        } else {
            $productCustom = ProductCustom::where('product', $product)->where('franchise', $franchise)->first();
            return ProductCustom::find($productCustom->id);
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
        $productCustom = ProductCustom::find($id);
        if ($productCustom->name == null && $productCustom->description == null && $productCustom->promotion == null && $productCustom->price == null) {
            $productCustom->delete();
        }
    }
}
