<?php

namespace devuelving\core;

use devuelving\core\ProductModel;
use devuelving\core\CategoryModel;
use Illuminate\Database\Eloquent\Model;

class ProductCategoryModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_category';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product', 'category',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Función para obtener el producto
     *
     * @return void
     */
    public function getProduct()
    {
        return ProductModel::find($this->product);
    }

    /**
     * Función para obtener la categoria
     *
     * @return void
     */
    public function getCategory()
    {
        return CategoryModel::find($this->category);
    }
}