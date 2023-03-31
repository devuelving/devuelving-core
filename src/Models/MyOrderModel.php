<?php

namespace devuelving\core;

use App\Franchise;
use devuelving\core\TaxModel;
use devuelving\core\ProductModel;
use devuelving\core\MyCountryModel;
use devuelving\core\MyOrderDetailModel;
use Illuminate\Database\Eloquent\Model;
use devuelving\core\MyShippingFeesModel;
use devuelving\core\FranchiseCustomModel;
use devuelving\core\MyOrderDiscountModel;
use devuelving\core\MyPaymentMethodModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class MyOrderModel extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'my_shop_orders';

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
        'id', 'code', 'customer', 'franchise', 'status', 'volume', 'weight', 'boxes', 'amount', 'is_cost_price', 'franchise_earnings', 'added_taxes', 'payment_method', 'payment_method_cost', 'payment_method_data', 'shipping_costs', 'shipping_costs_customer', 'shipping_costs_franchise', 'delivery_term', 'customer_nif', 'customer_name', 'customer_email', 'customer_phone', 'address_street', 'address_number', 'address_floor', 'address_door', 'address_town', 'address_province', 'address_postal_code', 'address_country', 'comments',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    /**
     * Returns total amount to be paid
     *
     * @return float
     */
    public function totalAmount()
    {
        $order_price = 0;
        $order_details = MyOrderDetailModel::where('order', $this->id)->get();
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
        $order_price = 0;$order_price1 = 0;
        $order_details = MyOrderDetailModel::where('order', $this->id)->get();
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
        }
    }

    /**
     * Función para obtener el total del pedido sin gastos del método de pago
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getSubtotal()
    {
        if ($this->getShippingCosts() != null) {
            return $this->totalAmount() + $this->added_taxes + $this->getShippingCosts();
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
        $products = $this->listProducts();
        foreach ($products as $product) {
            if ($product->getProduct()->getProductProviderData('shipping_type') == 3) {
                return true;
            }
        }
        return false;
    }

    /**
     * Función para obtener el total del pedido con el vale de descuento restado
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getTotal()
    {
        if ($this->getDiscountCoupon() != null) {
            return number_format(($this->getSubtotal() + $this->getPaymentCost()) - $this->getDiscountCoupon()->discount_value, 2, '.', '');
        } else {
            return number_format($this->getSubtotal() + $this->getPaymentCost(), 2, '.', '');
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
        if (empty($this->payment_method) || !MyPaymentMethodModel::where('franchise', Franchise::getFranchise()->id)->where('id', $this->payment_method)->exists()) {
            $payment_method = MyPaymentMethodModel::where('franchise', Franchise::getFranchise()->id)->where('mode', 1)->first();
            $this->payment_method = $payment_method->id;
            $this->save();
        }
        return MyPaymentMethodModel::find($this->payment_method);
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
        return number_format(($this->getSubtotal() * ($this->getPaymentMethod()->porcentual / 100)) + $this->getPaymentMethod()->fixed, 2, '.', '');
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
        $details = MyOrderDetailModel::where('order', $this->id)->get();
        foreach ($details as $detail) {
            $earnings = $earnings + $detail->franchise_earning;
        }
        $discounts = 0;
        if (MyOrderDiscountModel::where('order', $this->id)->exists()) {
            $voucher = MyOrderDiscountModel::where('order', $this->id)->first();
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
    public function getShippingCosts()
    {
        if (!empty($this->address_country)) {
            $total = 0;
            $country = MyCountryModel::where('franchise', Franchise::getFranchise()->id)->where('code', $this->address_country)->first();
            $shippingFee = MyShippingFeesModel::find($country->shipping_fee);
            $weight =  $this->weight;
            if( $this->weight <  $this->volume){
                $weight = $this->volume;
            }
            $total = $this->getShippingPrice($shippingFee, $weight);
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
        // 19/07/2019 - No apliquem descomptes al ports de la tienda propia
        return 0;
        $product_total = $this->totalAmount();
        $free_shippings = FranchiseCustomModel::where('franchise', $this->franchise)->where('var', 'free_shipping')->first();
        if ($free_shippings){
            $total = false;
			$last_amount = 0;
            $free_shipping_array = json_decode($free_shippings->value);
            foreach ($free_shipping_array as $free_shipping) {
                if($product_total >= $free_shipping->amount && $free_shipping->amount > $last_amount) {
                    $last_amount = $free_shipping->amount;
                    $total = number_format($free_shipping->discount, 2, '.', '');
                }
            }
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
            'address_country' => MyCountryModel::where('code', $this->address_country)->where('franchise', Franchise::getFranchise()->id)->first(),
        ];
    }

    /**
     * Función para obtener el precio exacto según la tarifa de envio
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param ShippingFeeModel $shippingFee
     * @return void
     */
    public function getShippingPrice(MyShippingFeesModel $shippingFee, $weight)
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
        return [
            'products' => $this->totalAmount(),
            'payment_method' => $this->getPaymentCost(),
            'amount' => $this->getTotal(),
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
        return MyOrderDetailModel::where('order', $this->id)->count();
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
        return MyOrderDetailModel::where('order', $this->id)->get();
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
        return MyOrderDiscountModel::where('order', $this->id)->where('type', 1)->first();
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
        return MyOrderDiscountModel::where('order', $this->id)->where('type', '!=', 1)->get();
    }

    /**
     * Función para comprobar si en el pedido hay productos de un proveedor
     * Devuelve 0 Si no hay carne. 1 Si hay.
     *
     * @return void
     */
    public function checkProviderOrder($provider)
    {
        $order_lines = MyOrderDetailModel::where('order', $this->id)->get();
        foreach ($order_lines as $order_line) {
            $product = ProductModel::find($order_line->product);
            if ($product->getProvider()->id == $provider) {
                return true;
            }
        }
        return false;
    }
}