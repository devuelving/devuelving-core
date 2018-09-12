<?php

namespace devuelving\core;

use devuelving\core\Cart;
use devuelving\core\Franchise;
use devuelving\core\Incidents;
use devuelving\core\OrderDetail;
use devuelving\core\OrderHistory;
use devuelving\core\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
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
        'code', 'customer', 'franchise', 'status', 'volume', 'weight', 'customer_name', 'customer_email', 'customer_phone', 'amount', 'franchise_earnings', 'added_taxes', 'address_street', 'address_number', 'address_floor', 'address_door', 'address_town', 'address_province', 'address_postal_code', 'address_country', 'payment_method', 'payment_method_data', 'notes', 'cost_price_purchase',
    ];

    /**
     * Returns true if order has incidents
     *
     * @return boolean
     */
    public function hasIncidents()
    {
        $incidents = Incidents::where('order_id', '=', $this->id);
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
        $order_details = OrderDetail::where('order', $this->id)->get();
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
     * Función para obtener el método de pago
     *
     * @return void
     */
    public function paymentMethodName()
    {
        $method = PaymentMethod::find($this->payment_method);
        return $method->name;
    }

    /**
     * Función para obtener el total del pedido
     *
     * @return void
     */
    public function getTotalOrderCost()
    {
        return $this->totalAmount() + $this->added_taxes;
    }

    /**
     * Función para obtener los gastos de gestión de paypal
     *
     * @return void
     */
    public function getPayPalCost()
    {
        return ($this->getTotalOrderCost() * 0.035) + 0.35;
    }

    /**
     * Función para listar los productos de un pedido
     *
     * @return OrderDetail
     */
    public function listProducts()
    {
        return OrderDetail::where('order', $this->id)->get();
    }

    /**
     * Función para iniciar un pedido
     *
     * @return string
     */
    public static function init()
    {
        if (session('priceCost') == '1') {
            $priceCost = '1';
        } else {
            $priceCost = '0';
        }
        if (session('equivalenceTax') == '1') {
            $equivalenceTax = '1';
        } else {
            $equivalenceTax = '0';
        }
        $addedTaxes = 0;
        $order = new Order();
        $order->customer = auth()->user()->id;
        $order->franchise = Franchise::getFranchise();
        $order->status = 1;
        $order->amount = 0;
        $order->customer_name = auth()->user()->name;
        $order->customer_email = auth()->user()->email;
        $order->customer_phone = auth()->user()->phone;
        $order->save();
        $orderHistory = new OrderHistory();
        $orderHistory->order = $order->id;
        $orderHistory->status = $order->status;
        $orderHistory->save();
        $cartProducts = Cart::where('customer', auth()->user()->id)->get();
        foreach ($cartProducts as $cartProduct) {
            $product = Product::find($cartProduct->product);
            $orderDetail = new OrderDetail();
            $orderDetail->type = 1;
            $orderDetail->status = 1;
            $orderDetail->order = $order->id;
            $orderDetail->customer = $order->customer;
            $orderDetail->product = $product->id;
            $orderDetail->units = $cartProduct->units;
            if ($priceCost == '1') {
                $orderDetail->unit_price = $product->getPublicPriceCost();
                $orderDetail->franchise_earning = 0;
                $addedTaxes += ($product->getPublicPriceCostWithoutIva() * $cartProduct->units) * 0.052;
            } else {
                $orderDetail->unit_price = $product->getPrice();
                $orderDetail->franchise_earning = $product->getProfit() * $cartProduct->units;
                $addedTaxes += ($product->getPriceWithoutIva() * $cartProduct->units) * 0.052;
            }
            $orderDetail->save();
        }
        $order->code = "P-" . rand(1000, 9999) . "-" . rand(1000, 9999) . "-" . str_pad((int)$order->id, 6, "0", STR_PAD_LEFT);
        if ($equivalenceTax == '1') {
            $order->added_taxes = $addedTaxes;
        }
        $order->amount = $order->totalAmount();
        $order->save();
        session(['order' => $order->code]);
        session(['priceCost' => $priceCost]);
        session(['equivalenceTax' => $equivalenceTax]);
        return $order->code;
    }
}
