<?php

namespace devuelving\core;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class CategoryImageModel extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category_image';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category', 'image', 'franchise'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 
    ];

    public $timestamps = false;
}
