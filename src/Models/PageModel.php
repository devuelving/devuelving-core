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
        if (FranchiseModel::getFranchise()->type == 0){
            $return = str_replace('[shop_data]', $this->getDefaultData(), $return);
            $return = str_replace('[myshop_terms]', $this->getDefaultTerms(), $return);
        }else{
            $return = str_replace('[myshop_terms]', $this->getMyShopTerms(), $return);
            $return = str_replace('[shop_data]', $this->getMyShopData(), $return);
        }
        /*if (FranchiseServicesModel::where('franchise', FranchiseModel::getFranchise()->id)->where('service', 'tienda_propia')->where('value', '1')->exists()){
        $return = str_replace('[myshop_terms]', $this->getMyShopTerms(), $return);
        }
        else{
            $return = str_replace('[myshop_terms]', '', $return);
        }*/
        return $return;
    }
    

    /**
     * Función para reemplazar los shorcodes del contenido
     *
     * @return void
     */
    public function getDefaultData(){

        $franchise = FranchiseModel::getFranchise();
        $franchiseContactData = $franchise->getFranchiseContactData();
        $return = '<p>La plataforma de gestión y mantenimiento de esta web  hace parte de la franquicia Devuelving . Los derechos y licencias de explotación de la marca, imagen y logotipo DEVUELVING están vinculados y autorizados por la empresa <strong>DIGITAL COMPANY SHOPONLINE LLC.</strong>
siendo esta última la gestora, domiciliada en 16192 Coastal HWY Lewes Delaware 19958 Estados Unidos,con identificación EIN 85-3847296  que autoriza  y colabora en el territorio nacional Español con la empresa DT Tecnología 2007, S.L., 
sociedad de nacionalidad española domiciliada en Ronda Ibérica 13 3B 08800 Vilanova i la Geltrú (Barcelona). DT Tecnología 2007 S.L. Está inscrita en el Registro Mercantil de Barcelona, en el Tomo 39447, Folio 156, Hoja núm. B-345984, inscripción 1ª, 
con número de CIF B-64503238.</p><br>';

        return $return;

    }
    /**
     * Función para reemplazar los shorcodes del contenido
     *
     * @return void
     */
    public function getDefaultTerms(){

        $franchise = FranchiseModel::getFranchise();
        $franchiseContactData = $franchise->getFranchiseContactData();
        $return = '<h3><strong>¿Quién es el Responsable del tratamiento de sus datos? </strong></h3>
                <p>DT TECNOLOGÍA 2007, S.L. (o DEVUELVING) actúa como responsable del tratamiento de datos.</p>
                <table border="1">
                    <tbody>
                    <tr>
                        <td style="padding:10px;">
                        <u>Datos identificativos de DEVUELVING</u><br /><br />
                        <strong>Identidad:</strong>DT TECNOLOGÍA 2007, S.L.<br />
                        <strong>C.I.F.:</strong> B-64503238<br />
                        <strong>Dirección postal:</strong> Ronda Ibérica nº13 nave B3, C.P 08800, Vilanova i la Geltrú (Barcelona, España)<br />
                        <strong>Correo electrónico:</strong> info@devuelving.com .<br />
                        </td>
                    </tr>
                    </tbody>
                </table><br>';

        return $return;

    }
    
    /**
     * Función para reemplazar los shorcodes del contenido
     *
     * @return void
     */
    public function getMyShopTerms(){

        $return = '<h3><strong>¿Quién es el Responsable del tratamiento de sus datos? </strong></h3>';
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
    public function getMyShopData(){

        $franchise = FranchiseModel::getFranchise();
        $franchiseContactData = $franchise->getFranchiseContactData();
        $return = '<p>La plataforma de gestión y mantenimiento de esta web  hace parte de la franquicia Devuelving . Los derechos y licencias de explotación de la marca, imagen y logotipo ' . strtoupper($franchise->name) .' están vinculados a &nbsp;' . $franchiseContactData->name . '&nbsp;' . $franchiseContactData->surname . '
        con NIF/CIF &nbsp;' . $franchiseContactData->nif . ' y con domicilio en &nbsp;' . $franchiseContactData->street . '&nbsp;' . $franchiseContactData->number . '&nbsp;' . $franchiseContactData->floor . '&nbsp;' . $franchiseContactData->door . '&nbsp;' .
            $franchiseContactData->postal_code . '&nbsp;' . $franchiseContactData->town . '&nbsp;(' . $franchiseContactData->province . ')&nbsp;. <br>';

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
        $return = strtoupper($franchise->name).'&nbsp; actúa como responsable del tratamiento de datos.</p>'.
        '<table border="1">' .
        '<tbody>' .
        '<tr>' .
            '<td style="padding:10px;">' .
            '<u>Datos identificativos de ' . strtoupper($franchise->name) .'</u><br /><br />' .
            '<strong>Identidad:</strong>&nbsp;' . $franchiseContactData->name . '&nbsp;' . $franchiseContactData->surname . '<br />' .
            '<strong>C.I.F.:</strong>&nbsp;' . $franchiseContactData->nif . '<br />' .
            '<strong>Dirección postal:</strong>&nbsp;' . $franchiseContactData->street . '&nbsp;' . $franchiseContactData->number . '&nbsp;' . $franchiseContactData->floor . '&nbsp;' . $franchiseContactData->door . '&nbsp;' .
            $franchiseContactData->postal_code . '&nbsp;' . $franchiseContactData->town . '&nbsp;(' . $franchiseContactData->province . ')&nbsp;-&nbsp;' . $franchiseContactData->country . '<br />' .            
            '</td>' .
        '</tr>' .
        '</tbody>' .
    '</table><br>';

        return $return;

    }


}
