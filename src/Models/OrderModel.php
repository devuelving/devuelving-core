<?php

namespace devuelving\core;

use devuelving\core\RegionModel;
use devuelving\core\IncidentsModel;
use devuelving\core\OrderDetailModel;
use devuelving\core\ShippingFeeModel;
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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code', 'customer', 'franchise', 'status', 'volume', 'weight', 'boxes', 'amount', 'cost_price_purchase', 'franchise_earnings', 'added_taxes', 'payment_method', 'payment_method_cost', 'payment_method_data', 'discount_voucher', 'discount_voucher_value', 'delivery_term', 'customer_name', 'customer_email', 'customer_phone', 'address_street', 'address_number', 'address_floor', 'address_door', 'address_town', 'address_province', 'address_postal_code', 'address_country', 'comments', 'created_at',
    ];

    /**
     * Returns true if order has incidents
     *
     * @return boolean
     */
    public function hasIncidents()
    {
        $incidents = IncidentsModel::where('order_id', '=', $this->id);
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
            case 1:
                return "Pendiente de pago";
                break;
            case 2:
                return "Pagado";
                break;
            case 3:
                return "En preparación";
                break;
            case 4:
                return "Preparado";
                break;
            case 5:
                return "Enviado";
                break;
            case 6:
                return "Entregado";
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
     * Función para obtener el total del pedido sin 
     *
     * @return void
     */
    public function getTotal()
    {
        return $this->getSubtotal() + $this->getPaymentCostCost();
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
                $country = Country::where('code', $this->address_country)->first();
                $shippingFee = ShippingFeeModel::find($country->default_shipping_fee);
            }
            $total = $this->getShippingPrice($shippingFee, $this->weight);
            return $total;
        }
        return null;
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
     * Función para listar los productos de un pedido
     *
     * @return OrderDetailModel
     */
    public function listProducts()
    {
        return OrderDetailModel::where('order', $this->id)->get();
    }
}
