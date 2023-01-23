<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\Services\SlugService;

class CategoryCustomModel extends Model
{
    use Sluggable;

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
        'category', 'name', 'description', 'meta_title', 'meta_description', 'meta_keywords', 'franchise', 'status', 'slug', 'banner'
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
        return $query->whereNull('franchise')->orWhere('franchise', $model->franchise);
    }

    protected static function boot()
    {
        static::registerModelEvent('slugging', static function($model) {
            $default = request('slug_default');
            $parent = request('slug_parent');
            $input = request('slug');

            if(empty($model->slug) && empty($input) || $input == $parent) {
                $model->slug = $parent;
                return false;
            }

            if($default !==  $input) {
                $slug = SlugService::createSlug(CategoryCustomModel::class, 'slug', $input);
                if(CategoryModel::where("id", "!=", $model->category)->whereNull("franchise")->where("slug", $slug)->exists()) {
                    $model->slug = (empty($default)) ? request('slug_parent') : $default;
                    return false;
                } else {
                    $model->slug = $slug;
                }
            } else {
                return false;
            }
        });
        
        static::registerModelEvent('slugged', static function($model) {
            //info('Category slugged: ' . $model->slug);
        });

        /* //When only have it, can work
        static::created(function ($model) {
            
        });

        //together cant work
        static::updated(function ($model) {
            
        }); */
        
        parent::boot();
    }
}
