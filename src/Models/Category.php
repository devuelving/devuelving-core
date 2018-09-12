<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use Sluggable;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'slug', 'name', 'description', 'parent', 'has_products', 'franchise', 'meta_title', 'meta_description', 'meta_keywords',
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
                'source' => 'name'
            ]
        ];
    }

    /**
     * Función para obtener un listado con las categorías
     *
     * @param int $parent
     * @return void
     */
    public function arrayCategories($parent = 0)
    {
        $return = [];
        $categories = Category::where('franchise', 0)->where('parent', $parent)->get();
        foreach ($categories as $category) {
            $child_categories = [];
            if ($category->has_products == 0) {
                $child_categories = $category->arrayCategories($category->id);
            }
            $return[] = [
                'id' => $category->id,
                'name' => $category->name,
                'parent' => $category->parent,
                'has_products' => $category->has_products,
                'image' => env('API_URL') . $category->getImage($category->id),
                'edit' => route('category.edit', $category->id),
                'child_categories' => $child_categories
            ];
        }
        return $return;
    }

    /**
     * Función para obtener un listado con las categorías
     *
     * @param int $parent
     * @return void
     */
    public function arrayCategoriesOwnShop($parent = 0)
    {
        $return = [];
        $categories = Category::where('franchise', \Auth::User()->franchise)->where('parent', $parent)->get();
        foreach ($categories as $category) {
            $child_categories = [];
            if ($category->has_products == 0) {
                $child_categories = $category->arrayCategoriesOwnShop($category->id);
            }
            $return[] = [
                'id' => $category->id,
                'name' => $category->name,
                'parent' => $category->parent,
                'has_products' => $category->has_products,
                'image' => env('API_URL') . $category->getImage($category->id),
                'edit' => route('own-shop.category.edit', $category->id),
                'child_categories' => $child_categories
            ];
        }
        return $return;
    }

    /**
     * Función para obtener la imagen de una categoría
     *
     * @param int $category
     * @return void
     */
    public function getImage($category)
    {
        $categoryImage = DB::table('category_image')->where('category', $category)->where('franchise', 0)->get();
        foreach ($categoryImage as $category) {
            return $category->image;
        }
    }

    /**
     * Función para obtener el nombre de la categoría padre
     *
     * @param int $parent
     * @return void
     */
    public function getParent($parent)
    {
        if ($parent == '0') {
            return __('Categoría Principal');
        } else {
            $category = Category::find($parent);
            return $category->name;
        }
    }

    /**
     * Función para obtener el nombre de la categoria con todas las padres
     *
     * @param int $parent
     * @param string $icon
     * @return void
     */
    public function listCategoriesName($parent, $icon = "<i class='fas fa-angle-double-right'></i>")
    {
        if ($parent != '0') {
            $category = Category::find($parent);
            return $category->listCategoriesName($category->parent) . ' ' . $icon . ' ' . $category->name;
        }
    }

    /**
     * Función para imprimir un banner de la categoria
     *
     * @return void
     */
    public function print()
    {
        return view('modules.catalog.category');
    }
}
