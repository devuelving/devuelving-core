<?php

namespace devuelving\core;

use Illuminate\Database\Eloquent\Model;

class HelpArticlesModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'help_articles';

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
        'name', 'text', 'category',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'created_at', 'updated_at',
    ];
    /**
     * Returns string with the status of the order
     *
     * @return string
     */
    public function helpArticleCategory()
    {
        switch ($this->category) {
            case 0:
                return __("Sin Categoría");
                break;
            case 1:
                return __("General");
                break;
            case 2:
                return __("Logística");
                break;
            case 3:
                return __("Informática");
                break;
            case 4:
                return __("Funcionamiento");
                break;
            case 5:
                return __("Marketing");
                break;            
        }
    }
    public function helpArticleColor()
    {
        switch ($this->category) {
            case 0:
                return __(" ");
                break;
            case 1:
                return __("red");
                break;
            case 2:
                return __("blue");
                break;
            case 3:
                return __("green");
                break;
            case 4:
                return __("purple");
                break;
            case 5:
                return __("cian");
                break;
        }
    }
}
