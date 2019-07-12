<?php

namespace devuelving\core;
use Illuminate\Database\Eloquent\Model;

class ProductImageModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_image';

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
        'image', 'product', 'default', 'franchise'
    ];
}
