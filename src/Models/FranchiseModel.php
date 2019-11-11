<?php

namespace devuelving\core;

use Carbon\Carbon;
use devuelving\core\CustomerModel;
use Illuminate\Database\Eloquent\Model;
use devuelving\core\CallAppointmentModel;
use devuelving\core\FranchiseServicesModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class FranchiseModel extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'franchise';

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
        'code', 'status', 'agent', 'name', 'domain', 'domain_status', 'domain_provider', 'company_type', 'start', 'irpf', 'bank_account', 'options', 'type'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'created_at', 'updated_at', 'deleted_at',
    ];

    /**
     * Método para obtener el logo de la franquicia
     *
     * @return void
     */
    public static function getLogo($redirect = true)
    {
        try {
            $franchise = new FranchiseModel();
            if ($franchise->getCustom('logo') != null) {
                // return config('app.cdn.url') . $franchise->getCustom('logo');
                // return '/cdn/' . $franchise->getCustom('logo');
                if ($redirect){
                return route('index') . '/cdn/' . $franchise->getCustom('logo');
                }
                else
                {
                return config('app.cdn.url') . $franchise->getCustom('logo');
                }
            } 
            return asset('images/app/brand/logo.png');
        } catch (\Exception $e) {
            return asset('images/app/brand/logo.png');
        }
    }

    /**
     * Método para obtener el icono de la franquicia
     *
     * @return void
     */
    public static function getIcon()
    {
        try {
            $franchise = new FranchiseModel();
            if ($franchise->getCustom('icon') != null) {
                // return config('app.cdn.url') . $franchise->getCustom('icon');
                // return '/cdn/' . $franchise->getCustom('icon');
                return route('index') . '/cdn/' . $franchise->getCustom('icon');
            }
            return asset('images/app/brand/icon.png');
        } catch (\Exception $e) {
            return asset('images/app/brand/icon.png');
        }
    }

    /**
     * Método para obtener el dominio de la franquicia actual
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public static function getDomain()
    {
        $domain = $_SERVER['HTTP_HOST'];
        $domain = str_replace("www.", "", $domain);
        return $domain;
    }

    /**
     * Método para obtener la franquicia por el dominio o usuario
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public static function getFranchise()
    {
        if (!empty(auth()->user()->franchise)) {
            return FranchiseModel::find(auth()->user()->franchise);
        } else if (session()->exists('franchise')){
            return session('franchise');
        } else if ($franchise = FranchiseModel::where('domain', FranchiseModel::getDomain())->first()) {
            session()->put('franchise', $franchise);
            return $franchise;
        } else {
            return FranchiseModel::where('code', str_replace('.tutienda.com.es', '', FranchiseModel::getDomain()))->first();
        }
    }

    /**
     * Metodo para obtener al franquiciado del dominio
     *
     * @since 3.0.0
     * @author Aaron <aaron@devuelving.com>
     * @return void
     */
    public static function getFranchiseContactData($franchise = NULL) 
    {
        if(!$franchise) {
            $franchise = FranchiseModel::getFranchise()->id;
        }
        return FranchiseContactDataModel::where('franchise', $franchise)->first();
    }

    /**
     * Función para obtener datos de la franquicia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public static function get($data = null)
    {
        $id = FranchiseModel::getFranchise()->id;
        if ($data) {
            try {
                $franchise = FranchiseModel::find($id);
                return $franchise->$data;
            } catch (\Exception $e) {
                // report($e);
                return null;
            }
        }
        return $id;
    }

    /**
     * Función para obtener la lista de clientes de la franquicia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function countClients()
    {
        $clients = CustomerModel::where('franchise', $this->id)->get();
        return count($clients) - 1;
    }

    /**
     * Función para obtener las variables perosnalizadas de la franquicia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getCustom($data = null)
    {
        if (!empty(auth()->user()->franchise)) {
            $id = auth()->user()->franchise;
        } else {
            $id = FranchiseModel::getFranchise()->id;
        }
        try {
            $franchise = FranchiseCustomModel::where('franchise', $id)->where('var', $data)->first();
            return $franchise->value;
        } catch (\Exception $e) {
            // report($e);
            return null;
        }
    }
    
    /**
     * Método para obtener variables customizadas de la franquicia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param string $var
     * @param string $default
     * @return void
     */
    public static function custom($var, $default = null)
    {
        try {
            $franchise = new FranchiseModel();
            if ($franchise->getCustom($var) != null) {
                return $franchise->getCustom($var);
            } else {
                return $default;
            }
        } catch (\Exception $e) {
            return $default;
        }
    }
    /**
     * Función para obtener las servicios perosnalizadas de la franquicia
     *
     * @since 3.0.0
     * @author 
     * @return void
     */
    public function getServices($data = null)
    {
        if (!empty(auth()->user()->franchise)) {
            $id = auth()->user()->franchise;
        } else {
            $id = FranchiseModel::getFranchise()->id;
        }
        try {
            info('id_franquicia->'.$id .' servicio->'.$data);
            $franchise = FranchiseServicesModel::where('franchise', $id)->where('service', $data)->first();
            return $franchise->value;
        } catch (\Exception $e) {
            // report($e);
            return null;
        }
    }
    /**
     * Método para obtener servicios de la franquicia
     *
     * @since 3.0.0
     * @author 
     * @param string $var
     * @param string $default
     * @return void
     */
    public static function services($var, $default = null)
    {
        try {
            $franchise = new FranchiseModel();
            if ($franchise->getServices($var) != null) {
                return $franchise->getServices($var);
            } else {
                return $default;
            }
        } catch (\Exception $e) {
            return $default;
        }
    }
    /**
     * Función para obtener las citas telefonicas de la franquicia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param string $type
     * @param date $date
     * @param string $date
     * @return void
     */
    public function getBooking($type = null, $date = null, $format = null)
    {
        $callAppointment = CallAppointmentModel::where('franchise', $this->id);
        if ($type != null) {
            $callAppointment->where('type', $type);
        }
        if ($date != null) {
            $callAppointment->where('date', $date);
        }
        if ($callAppointment->count() == 0) {
            return 'Sin Cita';
        }
        $callAppointments = $callAppointment->get();
        if ($format == 'text') {
            $return = '';
            foreach ($callAppointments as $callAppointment) {
                $return = 'Fecha: ' . Carbon::createFromFormat('Y-m-d', $callAppointment->date)->format('d-m-Y') . '<br>Hora: ' . substr($callAppointment->time, 0, -3) . '<br>';
            }
            return $return;
        }
        return $callAppointments;
    }
}
