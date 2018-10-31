<?php

namespace devuelving\core;

use devuelving\core\CategoryModel;
use Illuminate\Database\Eloquent\Model;

class ExportsModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'exports';

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
        'id', 'name', 'franchise', 'type', 'elements', 'filters',
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
     * Función para obtener los filtros
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getFilters()
    {
        if ($this->type = 2) {
            $filters = json_decode($this->filters, true);
            if ($filters['category'] == 0) {
                $category = 'Todas las categorias';
            } else {
                $category = CategoryModel::find($filters['category']);
                $category = $category->name;
            }
            if ($filters['iva'] == 0) {
                $iva = 'Todos los tipos de IVA';
            } else {
                $iva = Tax::find($filters['iva']);
                $iva = $iva->name;
            }
            return [
                'category' => $category,
                'iva' => $iva,
                'promotions' => $filters['promotions'],
            ];
        } else {
            return null;
        }
    }
}
