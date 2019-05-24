<?php

namespace devuelving\core;

use devuelving\core\CategoryCustomModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class CategoryCustomModel extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category_custom';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category', 'name', 'description', 'meta_title', 'meta_description', 'meta_keywords', 'franchise',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 
    ];
}
