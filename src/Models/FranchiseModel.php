<?php

namespace devuelving\core;

use Carbon\Carbon;
use devuelving\core\CustomerModel;
use devuelving\core\FranchiseModel;
use Illuminate\Database\Eloquent\Model;
use devuelving\core\CallAppointmentModel;
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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code', 'status', 'agent', 'name', 'domain', 'domain_provider', 'company_type', 'start', 'irpf', 'bank_account', 'options'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
    ];
    
    /**
     * Método para obtener el dominio de la franquicia actual
     *
     * @return void
     */
    public static function getDomain()
    {
        $domain = $_SERVER['HTTP_HOST'];
        $domain = str_replace("www.", "", $domain);
        return $domain;
    }

    /**
     * Método para obtener la franquicia por el dominio
     *
     * @return void
     */
    public static function getFranchise()
    {
        $franchise = FranchiseModel::where('domain', FranchiseModel::getDomain())->first();
        return $franchise->id;
    }

    /**
     * Función para obtener la lista de clientes de la franquicia
     *
     * @return void
     */
    public function countClients()
    {
        $clients = CustomerModel::where('franchise', $this->code)->get();
        return count($clients) - 1;
    }
    
    /**
     * Función para obtener datos de la franquicia
     *
     * @return void
     */
    public static function get($data = null)
    {
        if (!empty(auth()->user()->franchise)) {
            $code = auth()->user()->franchise;
        } else {
            $code = FranchiseModel::getFranchise();
        }
        if ($data) {
            try {
                $franchise = FranchiseModel::find($code);
                return $franchise->$data;
            } catch (\Exception $e) {
                // report($e);
                return null;
            }
        }
        return $code;
    }
    
    /**
     * Función para obtener las variables perosnalizadas de la franquicia
     *
     * @return void
     */
    public function getCustom($data = null)
    {
        $code = $this->code;
        if ($data && $code) {
            try {
                $franchise = FranchiseCustomModel::where('franchise', $code)->where('var', $data)->first();
                return $franchise->value;
            } catch (\Exception $e) {
                // report($e);
                return null;
            }
        }
        return "No existe";
    }

    /**
     * Función para obtener las citas telefonicas de la franquicia
     *
     * @param string $type
     * @param date $date
     * @param string $date
     * @return void
     */
    public function getBooking($type = null, $date = null, $format = null)
    {
        $callAppointment = CallAppointmentModel::where('franchise', $this->code);
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
