<?php

namespace devuelving\core;

use devuelving\core\FranchiseModel;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class PageModel extends Model
{
    use Sluggable;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pages';

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
        'slug', 'name', 'content', 'franchise'
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
                'source' => 'name'
            ]
        ];
    }
    /**
     * Función para reemplazar los shorcodes del contenido
     *
     * @return void
     */
    public function getContent()
    {
        $return = $this->content;
        $return = str_replace('[nombre_tienda]', strtoupper(FranchiseModel::get('name')), $return);
        if (FranchiseServicesModel::where('franchise', FranchiseModel::getFranchise()->id)->where('service', 'tienda_propia')->where('value', '1')->exists()){
        $return = str_replace('[myshop_terms]', $this->getMyShopTerms(), $return);
        }
        else{
            $return = str_replace('[myshop_terms]', '', $return);
        }
        return $return;
    }


    
    /**
     * Función para reemplazar los shorcodes del contenido
     *
     * @return void
     */
    public function getMyShopTerms(){

        $return = '<H3>Condiciones de la tienda propia</H3>';
        if (FranchiseCustomModel::where('franchise', FranchiseModel::getFranchise()->id)->where('var', 'myshop_terms')->exists()){
        $return = $return . '<p>' . FranchiseCustomModel::where('franchise', FranchiseModel::getFranchise()->id)->where('var', 'myshop_terms')->first()->value .'</p>';
        }
        $return = $return . $this->getMyShopOwner(); 
        return $return;

    }

/**
     * Función para reemplazar los shorcodes del contenido
     *
     * @return void
     */
    public function getMyShopOwner(){

        $franchise = FranchiseModel::getFranchise();

        $return = '<table border="1">' .
        '<tbody>' .
        '<tr>' .
            '<td style="padding:10px;">' .
            '<u>Datos identificativos de ' . strtoupper($franchise->name) .'</u><br /><br />' .
            '<strong>Identidad:</strong>&nbsp;' . $franchise->getFranchiseContactData()->name . '&nbsp;' . $franchise->getFranchiseContactData()->surname . '<br />' .
            '<strong>C.I.F.:</strong>&nbsp;' . $franchise->getFranchiseContactData()->nif . '<br />' .
            '<strong>Dirección postal:</strong>&nbsp;' . $franchise->getFranchiseContactData()->street . '&nbsp;' . $franchise->getFranchiseContactData()->number . '&nbsp;' . $franchise->getFranchiseContactData()->floor . '&nbsp;' . $franchise->getFranchiseContactData()->door . '<br />' .
            $franchise->getFranchiseContactData()->postal_code . '&nbsp;' . $franchise->getFranchiseContactData()->town . '&nbsp;' . $franchise::getFranchiseContactData()->province . '&nbsp;' . $franchise->getFranchiseContactData()->country . '<br />' .
            '<strong>Correo electrónico:</strong>&nbsp;' . $franchise->getFranchiseContactData()->email . '<br />' .
            '<strong>Teléfono:</strong>&nbsp;' . $franchise->getFranchiseContactData()->phone . '<br />' .
            '</td>' .
        '</tr>' .
        '</tbody>' .
    '</table><br>';

        return $return;

    }


}
