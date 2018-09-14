<?php

namespace devuelving\core;

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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product', 'category',
    ];
}