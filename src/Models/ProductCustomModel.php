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
        'product', 'franchise', 'promotion', 'free_shipping', 'price', 'price_type', 'name', 'description', 'meta_title', 'meta_description', 'meta_keywords', 'removed',
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
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();
        
        self::created(function($productCustom){
            $productCustom->checkClear();
        });

        self::updated(function($productCustom){
            $productCustom->checkClear();
        });
    }

    /**
     * Función para eliminar el registro de la base de datos, si no hay ningun elemento personalizado
     *
     * @return void
     */
    protected function checkClear()
    {
        if ($this->promotion == null && $this->free_shipping == null && $this->price == null && $this->price_type == null && $this->name == null && $this->description == null && $this->meta_title == null && $this->meta_description == null && $this->meta_keywords == null && $this->removed == 0) {
            $this->delete();
        }
    }
}
