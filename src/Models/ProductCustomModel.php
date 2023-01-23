<?php

namespace devuelving\core;

use App\Product;
use devuelving\core\FranchiseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\Services\SlugService;

class ProductCustomModel extends Model
{
    use Sluggable;
    
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
        'product', 'franchise', 'promotion', 'free_shipping', 'price', 'price_type', 'slug', 'name', 'description', 'meta_title', 'meta_description', 'meta_keywords', 'removed',
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
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable()
    {
        return [
            'slug' => [
                'source' => ['slug'],
                'unique' => true,
                'onUpdate' => true,
                'maxLength' => 191,
            ]
        ];
    }
    
    public function scopeWithUniqueSlugConstraints(Builder $query, Model $model, $attribute, $config, $slug) {
        return $query->where('franchise', FranchiseModel::getFranchise()->id);
    }

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

        static::registerModelEvent('slugging', static function($model) {
            //info('Product slugging: ' . $model->slug);

            $product_slug = request('product_slug');
            $slug_input = request('slug');

            if(empty($model->slug) && empty($slug_input)) {
                return false;
            } else if(empty($slug_input) || !empty($slug_input) && $slug_input == $product_slug) {
                return false;
            }

            $slug = SlugService::createSlug(ProductCustomModel::class, 'slug', $slug_input, ['unique' => false]);

            // Existe SLUG de un producto con frnachise null
            if(Product::where("id", "!=", $model->product)->whereNull("franchise")->where("slug", $slug)->exists()) {
                return false;
            }
            // Existe SLUG de un producto customizado 
            else if(ProductCustomModel::where("id", "!=", $model->id)->where("franchise", FranchiseModel::getFranchise()->id)->where("slug", $slug)->exists()) {
                return false;
            }

            // Set slug
            $model->slug = $slug;
        });
        
        static::registerModelEvent('slugged', static function($model) {
            //info('Category slugged: ' . $model->slug);
        });                
    }

    /**
     * Función para eliminar el registro de la base de datos, si no hay ningun elemento personalizado
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    protected function checkClear()
    {
        if ($this->promotion == null && $this->free_shipping == null && $this->price == null && $this->price_type == null && $this->name == null && $this->description == null && $this->meta_title == null && $this->meta_description == null && $this->meta_keywords == null && $this->slug == null && $this->removed == 0 && $this->tags == null) {
            $this->delete();
        }
    }
}
