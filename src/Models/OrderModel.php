<?php

namespace devuelving\core;

use App\Franchise;
use devuelving\core\TaxModel;
use devuelving\core\RegionModel;
use devuelving\core\CountryModel;
use devuelving\core\IncidentsModel;
use devuelving\core\OrderDetailModel;
use devuelving\core\ShippingFeeModel;
use devuelving\core\OrderDiscountModel;
use devuelving\core\PaymentMethodModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class OrderModel extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';

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
        'code', 'customer', 'franchise', 'status', 'volume', 'weight', 'boxes', 'amount', 'products_amount', 'discount_coupon_amount', 'gift', 'is_cost_price', 'franchise_earnings', 'added_taxes', 'payment_method', 'payment_method_cost', 'payment_method_data', 'shipping_costs', 'shipping_costs_customer', 'shipping_costs_franchise', 'delivery_term', 'customer_nif', 'customer_name', 'customer_email', 'customer_phone', 'address_street', 'address_number', 'address_floor', 'address_door', 'address_town', 'address_province', 'address_postal_code', 'address_country', 'comments', 'delivery_bill',
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
    * Get the franchise that owns the phone.
    */
    public function orderFranchise()
    {
        return $this->belongsTo('devuelving\core\FranchiseModel', 'franchise', 'id');
    }
    /**
    * Get the user that owns the phone.
    */
    public function orderCustomer()
    {
        return $this->belongsTo('devuelving\core\CustomerModel', 'customer', 'id');
    } 
    /**
     * Relationship order order_details hasMany
     */
    public function orderDetails()
    {
        return $this->hasMany('devuelving\core\OrderDetailModel', 'order', 'id');
    }
    /**
     * Relationship order order_notes hasMany
     */
    public function orderNotes()
    {
        return $this->hasMany('devuelving\core\OrderNotesModel', 'order', 'id');
    }
    /**
     * Relationship order order_notes hasOne
     */
    public function orderWithBill()
    {
        return $this->hasOne('devuelving\core\OrderBillsModel', 'order', 'id');
    }
    /**
     * Returns true if order has incidents
     *
     * @return boolean
     */
    public function hasIncidents()
    {
        $incidents = IncidentsModel::where('order', '=', $this->id);
        if ($incidents->count()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns total amount to be paid
     *
     * @return float
     */
    public function totalAmount()
    {
        //info('paso por totalAmount');
        $order_price = 0;
        $order_details = OrderDetailModel::where('order', $this->id)->get();
        foreach ($order_details as $order_detail) {
            $order_price = $order_price + ($order_detail->units * $order_detail->unit_price);
        }
        return number_format($order_price, 2, '.', '');
    }

    /**
     * Returns total amount to be paid NOT TAXES
     *
     * @return float
     */
    public function totalAmountWithOutTaxes()
    {
        $order_price = 0;
        $order_price1 = 0;
        $order_details = OrderDetailModel::where('order', $this->id)->get();
        foreach ($order_details as $order_detail) {
            $order_price = $order_price + ($order_detail->units * ($order_detail->unit_price / (1 + ($order_detail->tax / 100))));
        }
        return number_format($order_price, 2, '.', '');
    }

    /**
     * Returns string with the status of the order
     *
     * @return string
     */
    public function orderStatus()
    {
        $status =  DB::table('order_status')->where('status', $this->status)->first();
        if(!empty($status)){
            return $status->name;
        }else{
            return 'Desconocido';  
        }
        
        /*
        switch ($this->status) {
            case 0:
                return __("Sin finalizar");
                break;
            case 1:
                if ($this->payment_method == 3 || $this->payment_method == 4) {
                    return __("En gestión");
                } else {
                    return __("Pendiente de pago");
                }
                break;
            case 2:
                return __("Pagado");
                break;
            case 3:
                return __("En preparación");
                break;
            case 4:
                return __("Preparado");
                break;
            case 5:
                return __("Enviado");
                break;
            case 6:
                return __("En transito");
                break;
            case 7:
                return __("En reparto");
                break;
            case 8:
                return __("Entregado");
                break;
            case 9:
                return __("Devuelto");
                break;
            case 10:
                return __("Cancelado");
                break;
            case 11:
                return __("Incidencia");
                break;
        }*/
    }

    /**
     * Función para obtener el total del pedido = total productos + tasas + gastos de envío (no se tienen en cuenta gastos del método de pago)
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getSubtotal()
    {        
        //info('paso por getSubtotal() model');
        $shippingCostsCustomer = $this->shipping_costs_customer;//$this->getShippingCostsData()['shipping_costs_customer'];
        if ($shippingCostsCustomer != null) {
            return $this->totalAmount() + $this->added_taxes + $shippingCostsCustomer;
        }
        return number_format($this->totalAmount() + $this->added_taxes, 2, '.', '');
    }

    /**
     * Checks if the order has meat products
     *
     * @return boolean
     */
    public function hasDropshipping()
    {
        /* 15/12/2022 deprecated. No tenemos carne
        $products = $this->listProducts();
        foreach ($products as $product) {
            if ($product->getProduct()->getProductProviderData('shipping_type') == 3) {
                return true;
            }
        }*/
        return false;
    }

    /**
     * Función para obtener el total del pedido = total de productos + gastos de envío + recargoequuivalencia + gastos de método de pago - vale descuento
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getTotal()
    {
        //info('paso por getTotal()');
        $getDiscountCoupon = $this->getDiscountCoupon();
        $paymentMethod = $this->getPaymentMethod();
        $shippingcosts = $this->shipping_costs_customer;//$this->getShippingCostsData()['shipping_costs_customer'];
        $totalproductes = $this->totalAmount();
        $subtotal = $totalproductes + $this->added_taxes + $shippingcosts;
        $paymentmethodcost = ($subtotal * ($paymentMethod->porcentual / 100)) + $paymentMethod->fixed;
        if ($getDiscountCoupon != null) {
            $total = $subtotal +  $paymentmethodcost - $getDiscountCoupon->discount_value;
            if ($total <= 0) {
                $total = 0;
            }
            return number_format($total, 2, '.', '');
        } else {
            return number_format($subtotal + $paymentmethodcost, 2, '.', '');
        }
    }

    /**
     * Función para obtener el método de pago
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getPaymentMethod()
    {
        if (empty($this->payment_method)) {
            $this->payment_method = 1;
            $this->save();
        }
        return PaymentMethodModel::find($this->payment_method);
    }

    /**
     * Función para obtener los gastos de gestión del método de pago
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getPaymentCost()
    {
        $paymentMethod = $this->getPaymentMethod();
        //return $paymentMethod;
        return number_format(($this->getSubtotal() * ($paymentMethod->porcentual / 100)) + $paymentMethod->fixed, 2, '.', '');
    }

    /**
     * Returns the earnings that the franchisee has made with the order
     *
     * @since 3.0.0
     * @author Aaron <aaron@devuelving.com>
     * @return void
     */
    public function getEarnings()
    {
        $earnings = 0;
        $details = OrderDetailModel::where('order', $this->id)->get();
        foreach ($details as $detail) {
            $earnings = $earnings + $detail->franchise_earning;
        }
        $discounts = 0;
        if (OrderDiscountModel::where('order', $this->id)->exists()) {
            $voucher = OrderDiscountModel::where('order', $this->id)->first();
            $discounts = $voucher->discount_value;
        }
        $earnings = $earnings - $this->shipping_costs_franchise - $discounts;
        return number_format($earnings, 2, '.', '');
    }

    /**
     * Función para obtener los gastos de envio
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getShippingCosts($excludeMeat = false)
    {
        if (!empty($this->address_country)) {
            $total = 0;

            $region = RegionModel::where('name', $this->address_province)->where('country', $this->address_country)->first();
            if (!empty($region)) {
                $shippingFee = ShippingFeeModel::find($region->shipping_fee);
            } else {
                $country = CountryModel::where('code', $this->address_country)->first();
                $shippingFee = ShippingFeeModel::find($country->shipping_fee);
            }
            //info('OrderModel weight: ' . $this->getShippingWeight(true));
            if ($excludeMeat) {
                // $total = $this->getShippingPrice($shippingFee, $this->getProductWeight(true));
                $total = $this->getShippingPrice($shippingFee, $this->getShippingWeight(true));
            } else {
                // $total = $this->getShippingPrice($shippingFee, $this->weight);
                $total = $this->getShippingPrice($shippingFee, $this->getShippingWeight());
            }

            if ($this->hasDropshipping()) {
                $total = $total + $this->getDropshippingPrice();
            }
            $total += $total * (TaxModel::find(1)->value / 100);
            return number_format($total, 2, '.', '');
        }
        return null;
    }

    /**
     * Returns the amount of free shipping the franchisee is going to give the client
     * 
     * @since 3.0.0
     * @author Aaron Bujalance Garcia <aaron@devuelving.com>
     * @return void
     */
    public function getFreeShipping()
    {
        //info('paso por getFreeShipping()'); 
        if(Franchise::getFranchise()->type == 3){                
                //info('ENVIOS: Límite mensual '.json_decode(auth()->user()->subscription)->limit_orders.' con descuento: '.json_decode(auth()->user()->subscription)->free_shipping_orders. ' para destino nacionales con descuento de '.json_decode(auth()->user()->subscription)->free_shipping_discount);
                if(auth()->user()->subscriptionFreeShipping() && $this->address_country == 'es'){
                    return json_decode(auth()->user()->subscription)->free_shipping_discount; 
                }else{
                    return 0;   
                } 
        }
        $premiumOptions = json_decode(Franchise::custom('premium_options'));
        $orderDiscountPremium = auth()->user()->type == 3 && (Franchise::services('premium') && Franchise::getFranchise()->type != 3) && !(isset($premiumOptions->free_shipping) && $premiumOptions->free_shipping == true);
        if (session('priceCost') == 1 || $orderDiscountPremium) {
            return false;
        }
        
        $free_shippings = Franchise::custom('free_shipping');//FranchiseCustomModel::where('franchise', $this->franchise)->where('var', 'free_shipping')->first();
        $total = false;
        $last_amount = 0;
        if ($free_shippings && Franchise::getFranchise()->type != 3) {               
            $orderDiscount = $this->getDiscountCoupon();
            if (!empty($orderDiscount)) {
                //info('descuento a aplicar ->' . $orderDiscount->discount_value);
                $product_total = number_format($this->products_amount - $orderDiscount->discount_value, 2);
            } else {
                //info('no existe vale descuento');
                $product_total = $this->products_amount;
            }
            $free_shipping_array = json_decode($free_shippings);
            foreach ($free_shipping_array as $free_shipping) {
                if ($product_total >= $free_shipping->amount && $free_shipping->amount > $last_amount) {
                    //info('free_shipping_amount'.$free_shipping->amount);
                    $last_amount = $free_shipping->amount;
                    //$total = number_format($free_shipping->discount, 2, '.', '');
                    //cambio para coger el valor de pickupsi el pedido se recoge en tienda
                    if ($this->pickup) {
                        $total = number_format($free_shipping->discount_pickup, 2, '.', '');
                    } else {
                        $total = number_format($free_shipping->discount, 2, '.', '');
                    }
                }
            }
            
            
            //info('total_products->' . $product_total.' || total free_shipping->'.$total);
            return $total;
        } else {
            return false;
        }
    }

    /**
     * Método para obtener los datos del envio
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getShippingData()
    {
        return [
            'customer_nif' => $this->customer_nif,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'address_street' => $this->address_street,
            'address_number' => $this->address_number,
            'address_floor' => $this->address_floor,
            'address_door' => $this->address_door,
            'address_town' => $this->address_town,
            'address_province' => $this->address_province,
            'address_postal_code' => $this->address_postal_code,
            'address_country' => CountryModel::where('code', $this->address_country)->first(),
        ];
    }

    /**
     * Calcultes the price that is added by each of the dropshipping providers
     *
     * @return float
     */
    public function getDropshippingPrice()
    {
        $total = 0;
        $products = ProductModel::join('order_details', 'product.id', '=', 'order_details.product')
            ->where('order', $this->id)
            ->groupBy('product.provider')
            ->sum('weight');
        foreach ($products as $product) {
            if ($product->getProductProviderData('shipping_type') == 3) {
                $total = $total + $this->getShippingPrice(ShippingFeeModel::find($product->getProductProviderData('shipping_method')), $product->weight);
            }
        }
        return number_format($total, 2, '.', '');
    }

    /**
     * Función para obtener el precio exacto según la tarifa de envio
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param ShippingFeeModel $shippingFee
     * @return void
     */
    public function getShippingPrice(ShippingFeeModel $shippingFee, $weight)
    {
        switch (true) {
            case $weight < 2:
                return number_format($shippingFee->rate_2, 2, '.', '');
                break;
            case $weight < 3:
                return number_format($shippingFee->rate_3, 2, '.', '');
                break;
            case $weight < 5:
                return number_format($shippingFee->rate_5, 2, '.', '');
                break;
            case $weight < 7:
                return number_format($shippingFee->rate_7, 2, '.', '');
                break;
            case $weight < 10:
                return number_format($shippingFee->rate_10, 2, '.', '');
                break;
            case $weight < 15:
                return number_format($shippingFee->rate_15, 2, '.', '');
                break;
            case $weight < 20:
                return number_format($shippingFee->rate_20, 2, '.', '');
                break;
            case $weight < 30:
                return number_format($shippingFee->rate_30, 2, '.', '');
                break;
            case $weight < 40:
                return number_format($shippingFee->rate_40, 2, '.', '');
                break;
            case $weight > 40:
                return number_format($shippingFee->rate_40 + $this->getShippingPrice($shippingFee, $weight - 40), 2, '.', '');
                break;
        }
    }

    /**
     * Gets the weight of the items from a given provider
     *
     * @param int $provider
     * @return void
     */
    public function getProviderWeight($provider)
    {
        $weight = 0;
        $products = $this->listProducts();
        foreach ($products as $product) {
            if ($product->getProduct()->getProvider() == $provider) {
                $weight = $weight + $product->getProduct()->weight;
            }
        }
        return $weight;
    }

    /**
     * Obtener el resumen del pedido
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return array
     */
    public function getResume()
    {
        //info('paso por getResume() OrderModel');
        return [
            'products' => $this->totalAmount(),
            'payment_method' => $this->getPaymentCost(), /**$this->payment_method_cost,//** calcula la cantidad que le correspondería pero deberíamos ajustar para que si lo que queremos es obtener la información del pedido se obtuviera lo guardado*/
            'amount' => $this->amount,
            'earnings' => $this->franchise_earnings,
            'cost' => $this->getCost(),
        ];
    }

    /**
     * Obtiene el coste total del pedido
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getCost()
    {
        $total = 0;
        foreach ($this->listProducts() as $orderDetail) {
            $total += ($orderDetail->unit_price * $orderDetail->units) - $orderDetail->franchise_earning;
        }
        return number_format($total, 2, '.', '');
    }

    /**
     * Método para contar los productos de un pedido
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function countProducts()
    {
        return OrderDetailModel::where('order', $this->id)->count();
    }

    /**
     * Método para obtener los datos del método de pago
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getPaymentMethodData()
    {
        return json_decode($this->payment_method_data);
    }

    /**
     * Función para listar los productos de un pedido
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return OrderDetailModel
     */
    public function listProducts()
    {
        return OrderDetailModel::where('order', $this->id)->get();
    }

    /**
     * Metodo para obtener el vale descuento que se ha aplicado al pedido
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getDiscountCoupon()
    {
        //info('paso por getDiscountCoupon()');
        return OrderDiscountModel::where('order', $this->id)->where('type', 1)->first();
    }

    /**
     * Metodo para obtener los otros descuentos
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getOthersDiscounts()
    {
        //21/01/2022 cancelo la consulta.
        return null;//OrderDiscountModel::where('order', $this->id)->where('type', '!=', 1)->get();
    }

    /**
     * Método para obtener el listado de estados de un pedido
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getOrderShipmentStatus()
    {
        return OrderShipmentStatusModel::where('order', $this->id)->get();
    }

    /**
     * Función para comprobar si en el pedido hay productos de un proveedor
     * Devuelve 0 Si no hay carne. 1 Si hay.
     *
     * @return void
     */
    public function checkProviderOrder($provider)
    {
        $order_lines = OrderDetailModel::where('order', $this->id)->get();
        foreach ($order_lines as $order_line) {
            $product = ProductModel::find($order_line->product);
            if ($product->getProvider()->id == $provider) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets the weight from de order product detail
     *
     * @param bool $excludeMeat
     * @return void
     */
    public function getProductWeight($excludeMeat = false)
    {
        $weight = 0;
        $products = $this->listProducts();
        foreach ($products as $product) {
            if (!($product->getProduct()->getProvider()->id == 11 && $excludeMeat = true)) {
                $weight = $weight + $product->getProduct()->weight;
            }
        }
        return $weight;
    }

    /**
     * Rutina per obtenir el pes o el volum (el mes gran)
     *
     * @param bool $excludeMeat
     * @return void
     */
    public function getShippingWeight($excludeMeat = false)
    {
        $weight = 0;
        $volume = 0;
        $orderProducts = $this->listProducts();
        foreach ($orderProducts as $orderProduct) {
            $product = ProductModel::find($orderProduct->product);
            if (!(($product->getProvider()->id == 11 && $excludeMeat == true) ||
            ($product->transport == 1  && Franchise::custom('free_transport', true) && $this->address_country == 'es' && $this->address_province != 'Islas Baleares'))) {
                $weight += $product->weight * $orderProduct->units;
                $volume += $product->volume * $orderProduct->units;
            }
        }
        if ($weight > $volume) {
            return $weight;
        } else {
            return $volume;
        }
    }
}