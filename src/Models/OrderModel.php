<?php

namespace devuelving\core;

use devuelving\core\RegionModel;
use devuelving\core\CountryModel;
use devuelving\core\IncidentsModel;
use devuelving\core\OrderDetailModel;
use devuelving\core\ShippingFeeModel;
use devuelving\core\OrderDiscountModel;
use devuelving\core\PaymentMethodModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'code', 'customer', 'franchise', 'status', 'volume', 'weight', 'boxes', 'amount', 'is_cost_price', 'franchise_earnings', 'added_taxes', 'payment_method', 'payment_method_cost', 'payment_method_data', 'shipping_costs', 'shipping_costs_customer', 'shipping_costs_franchise', 'delivery_term', 'customer_name', 'customer_email', 'customer_phone', 'address_street', 'address_number', 'address_floor', 'address_door', 'address_town', 'address_province', 'address_postal_code', 'address_country', 'comments',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at', 'deleted_at',
    ];

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
        $order_price = 0;
        $order_details = OrderDetailModel::where('order', $this->id)->get();
        foreach ($order_details as $order_detail) {
            $order_price = $order_price + ($order_detail->units * $order_detail->unit_price);
        }
        return $order_price;
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
                return __("Entregado");
                break;
        }
    }

    /**
     * Función para obtener el total del pedido sin gastos del método de pago
     *
     * @return void
     */
    public function getSubtotal()
    {
        if ($this->getShippingCosts() != null) {
            return $this->totalAmount() + $this->added_taxes + $this->getShippingCosts();
        }
        return $this->totalAmount() + $this->added_taxes;
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
            if($product->getProduct()->getProductProviderData('shipping_type') == 3) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Función para obtener el total del pedido con el vale de descuento restado
     *
     * @return void
     */
    public function getTotal()
    {
        if ($this->getDiscountCoupon() != null) {
            return ($this->getSubtotal() + $this->getPaymentCostCost()) - $this->getDiscountCoupon()->discount_value;
        } else {
            return $this->getSubtotal() + $this->getPaymentCostCost();
        }
    }

    /**
     * Función para obtener el método de pago
     *
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
     * @return void
     */
    public function getPaymentCostCost()
    {
        return ($this->getSubtotal() * ($this->getPaymentMethod()->porcentual / 100)) + $this->getPaymentMethod()->fixed;
    }

    /**
     * Returns the earnings that the franchisee has made with the order
     *
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
        if(OrderDiscountModel::where('order', $this->id)->exists()){
            $voucher = OrderDiscountModel::where('order', $this->id)->first();
            $discounts = $voucher->discount_value;
        }
        $earnings = $earnings - $this->shipping_costs_franchise;
        return $earnings;
    }

    /**
     * Función para obtener los gastos de envio
     *
     * @return void
     */
    public function getShippingCosts()
    {
        if (!empty($this->address_country)) {
            $total = 0;
            if (RegionModel::where('name', $this->address_province)->where('country', $this->address_country)->count() == 1) {
                $region = RegionModel::where('name', $this->address_province)->where('country', $this->address_country)->first();
                $shippingFee = ShippingFeeModel::find($region->shipping_fee);
            } else {
                $country = CountryModel::where('code', $this->address_country)->first();
                $shippingFee = ShippingFeeModel::find($country->shipping_fee);
            }
            $total = $this->getShippingPrice($shippingFee, $this->weight);
            if ($this->hasDropshipping()) {
                $total = $total + $this->getDropshippingPrice();
            }
            return $total;
        }
        return null;
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
            if($product->getProductProviderData('shipping_type') == 3) {
                $total = $total + $this->getShippingPrice(ShippingFeeModel::find($product->getProductProviderData('shipping_method')), $product->weight);
            }
        }
        return $total;
    }

    /**
     * Función para obtener el precio exacto según la tarifa de envio
     *
     * @param ShippingFeeModel $shippingFee
     * @return void
     */
    public function getShippingPrice(ShippingFeeModel $shippingFee, $weight)
    {
        switch (true) {
            case $weight < 2:
                return $shippingFee->rate_2;
                break;
            case $weight < 3:
                return $shippingFee->rate_3;
                break;
            case $weight < 5:
                return $shippingFee->rate_5;
                break;
            case $weight < 7:
                return $shippingFee->rate_7;
                break;
            case $weight < 10:
                return $shippingFee->rate_10;
                break;
            case $weight < 15:
                return $shippingFee->rate_15;
                break;
            case $weight < 20:
                return $shippingFee->rate_20;
                break;
            case $weight < 30:
                return $shippingFee->rate_30;
                break;
            case $weight < 40:
                return $shippingFee->rate_40;
                break;
            case $weight > 40:
                return $shippingFee->rate_40 + $this->getShippingPrice($shippingFee, $weight - 40);
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
     * Función para listar los productos de un pedido
     *
     * @return OrderDetailModel
     */
    public function listProducts()
    {
        return OrderDetailModel::where('order', $this->id)->get();
    }

    /**
     * Metodo para obtener el vale descuento que se ha aplicado al pedido
     *
     * @return void
     */
    public function getDiscountCoupon()
    {
        return OrderDiscountModel::where('order', $this->id)->where('type', 1)->first();
    }

    /**
     * Metodo para obtener los otros descuentos
     *
     * @return void
     */
    public function getOthersDiscounts()
    {
        return OrderDiscountModel::where('order', $this->id)->where('type', '!=', 1)->get();
    }
}
