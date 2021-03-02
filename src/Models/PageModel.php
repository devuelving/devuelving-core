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
        $return = str_replace('[nombre_tienda]', strtoupper(FranchiseModel::getFranchise()->name), $return);
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
        $franchiseContactData = $franchise->getFranchiseContactData();
        $return = '<table border="1">' .
        '<tbody>' .
        '<tr>' .
            '<td style="padding:10px;">' .
            '<u>Datos identificativos de ' . strtoupper($franchise->name) .'</u><br /><br />' .
            '<strong>Identidad:</strong>&nbsp;' . $franchiseContactData->name . '&nbsp;' . $franchiseContactData->surname . '<br />' .
            '<strong>C.I.F.:</strong>&nbsp;' . $franchiseContactData->nif . '<br />' .
            '<strong>Dirección postal:</strong>&nbsp;' . $franchiseContactData->street . '&nbsp;' . $franchiseContactData->number . '&nbsp;' . $franchiseContactData->floor . '&nbsp;' . $franchiseContactData->door . '<br />' .
            $franchiseContactData->postal_code . '&nbsp;' . $franchiseContactData->town . '&nbsp;' . $franchiseContactData->province . '&nbsp;' . $franchiseContactData->country . '<br />' .
            '<strong>Correo electrónico:</strong>&nbsp;' . $franchiseContactData->email . '<br />' .
            '<strong>Teléfono:</strong>&nbsp;' . $franchiseContactData->phone . '<br />' .
            '</td>' .
        '</tr>' .
        '</tbody>' .
    '</table><br>';

        return $return;

    }


}
