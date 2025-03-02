<?php

namespace devuelving\core;

use Carbon\Carbon;
use devuelving\core\FranchiseModel;
use Illuminate\Database\Eloquent\Model;
use devuelving\core\CustomerPaymentsModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerModel extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer';

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
        'name', 'surname', 'email', 'password', 'phone', 'nif', 'birthdate', 'gender', 'nationality', 'status', 'verified', 'advertising', 'image', 'franchise', 'type', 'premium', 'vip', 'lang', 'options', 'subscription', 'remember_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'created_at', 'updated_at', 'deleted_at',
    ];

    /**
     * Relationship order customer hasMany
     */
    public function orders()
    {
        return $this->hasMany('devuelving\core\OrderModel', 'customer', 'id');
    }
    
    /**
     * Relationship order customer hasMany
     */
    public function address()
    {
        return $this->hasMany('devuelving\core\AddressModel', 'customer', 'id');
    }
    
    /**
     * Relationship customer customer_payments hasMany
     */
    public function payments()
    {
        return $this->hasMany('devuelving\core\CustomerPaymentsModel', 'customer', 'id');
    }
    
    /**
    * Get the customer that owns the comercial.
    */
    public function comercial()
    {
        return $this->belongsTo('devuelving\core\FranchiseComercialsModel', 'code', 'comercial');
    } 

    /**
     * Función para obtener los datos de un cliente
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param string $data
     * @return void
     */
    public function getData($data)
    {
        return $this->$data;
    }

    /**
     * Método para obtener los datos de la franquicia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function franchise()
    {
        return FranchiseModel::find($this->franchise);
    }

     /**
     * Método para comprobar si hay alguna suscripción activa
     *
     * @since 3.0.0
     * @author Eduard Puigdemunt <eduard@devuelving.com>
     * @return boolean
     */
    public function getActiveSubscription($date = false)
    {
        if ($date) {
            $now = $date;
        }
        else{
            $now = Carbon::now();
        }
        return CustomerPaymentsModel::where('customer', $this->id)
        ->where('status', 1) 
        // ->whereRaw(Carbon::now()->between(Carbon::parse($this->payment_date), Carbon::parse($this->expires_date)))
        ->whereRaw('"'.$now.'" between `payment_date` and `expires_date`')
        ->exists();
    }
     /**
     * Método que devuelve la suscripción activa
     *
     * @since 3.0.0
     * @author Eduard Puigdemunt <eduard@devuelving.com>
     * @return boolean
     */
    public function getSubscriptionPaid($type = null)
    {
        
        $now = Carbon::now();
        $subscription_paid = CustomerPaymentsModel::where('customer', $this->id)
        ->where('status', 1) 
        ->where('franchise', FranchiseModel::getFranchise()->id) 
        // ->whereRaw(Carbon::now()->between(Carbon::parse($this->payment_date), Carbon::parse($this->expires_date)))
        ->whereRaw('"'.$now.'" between `payment_date` and `expires_date`');
        if($type) $subscription_paid = $subscription_paid->where('type', $type);

        $subscription_paid = $subscription_paid->first();
        
        return $subscription_paid;
    }
}
