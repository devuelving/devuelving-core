<?php

namespace devuelving\core;

use Carbon\Carbon;
use devuelving\core\TaxModel;
use devuelving\core\BrandModel;
use devuelving\core\ProductCustom;
use devuelving\core\ProviderModel;
use Illuminate\Support\Facades\DB;
use devuelving\core\FranchiseModel;
use devuelving\core\OrderDetailModel;
use devuelving\core\ProductStockModel;
use devuelving\core\ProductCustomModel;
use Illuminate\Database\Eloquent\Model;
use devuelving\core\DiscountTargetsModel;
use devuelving\core\FranchiseCustomModel;
use devuelving\core\ProductCategoryModel;
use devuelving\core\ProductProviderModel;
use Cviebrock\EloquentSluggable\Sluggable;
use devuelving\core\ProductVariationModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductModel extends Model
{
    use Sluggable;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product';

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
        'slug', 'name', 'description', 'stock_type', 'minimum_stock', 'transport', 'weight', 'volume', 'tax', 'brand', 'tags', 'variations', 'franchise', 'promotion', 'free_shipping', 'double_unit', 'units_limit', 'liquidation', 'unavailable', 'discontinued', 'external_sale', 'highlight', 'price_edit', 'shipping_canarias', 'cost_price', 'recommended_price', 'default_price', 'profit_margin', 'franchise_profit_margin', 'price_rules', 'meta_title', 'meta_description', 'meta_keywords', 'net_quantity', 'unit'
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
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    /**
     *  Relationship brand hasOne
     */
    public function brands()
    {
        return $this->hasOne('devuelving\core\BrandModel', 'id', 'brand');
    }
    /**
     *  Relationship tax hasOne
     */
    public function productTax()
    {
        return $this->hasOne('devuelving\core\TaxModel', 'id', 'tax');
    }
    /**
     * Relationship product custom hasOne
     */
    public function productCustoms()
    {
        //return $this->hasMany('devuelving\core\ProductCustomModel', 'product', 'id');
        return $this->hasOne('devuelving\core\ProductCustomModel', 'product', 'id')->where('franchise', FranchiseModel::getFranchise()->id);
    }
    /**
     * Relationship product provider hasMany
     */
    public function productProvider()
    {
        return $this->hasMany('devuelving\core\ProductProviderModel', 'product', 'id')->with('provider_data');
    }
    
    /**
     * Relationship product provider hasMany
     */
    public function allCustom()
    {
        return $this->hasMany('devuelving\core\ProductCustomModel', 'product', 'id');
    }

    /**
     * Relationship product image hasMany
     */
    public function productImage()
    {
        return $this->hasMany('devuelving\core\ProductImageModel', 'product', 'id');
    }

    /**
     * Relationship product image hasMany
     */
    public function productImages()
    {
        $id = isset(FranchiseModel::getFranchise()->id) ? FranchiseModel::getFranchise()->id : 0;
        return $this->hasMany('devuelving\core\ProductImageModel', 'product', 'id')->where(function ($query) use ($id) {
            $query->whereNull('franchise')->orWhere('franchise', 0)->orWhere('franchise', $id);
        })->orderBy("franchise")->orderBy("id");
    }

    /**
     * Relationship product image hasMany - Only DEFAULT 
     */
    public function defaultImages()
    {
        return $this->productImages()->where(function ($query) {
            $query->whereNull('franchise')->orWhere('franchise', 0);
        });
    }

    /**
     * Relationship product image hasMany - Only Custom
     */
    public function customImages()
    {
        return $this->productImages()->where("franchise", FranchiseModel::getFranchise()->id);
    }

    /**
     * Main product image - Relationship product_image Array[0]
     */
    public function getMainImageAttribute()
    {   
        $model = $this->relationLoaded('productImages') ? $this->productImages->first() : $this->defaultImages()->first();
        if(empty($model) || empty($model->image)) {
            return '/product/default.png';
        }
        return $model->image;
    }

    /**
     * Relationship category hasMany
     */
    public function categories()
    {
        return $this->belongsToMany('devuelving\core\CategoryModel', 'product_category', 'product', 'category');
    }    
    /**
     * Relationship product hasMany variations
     */
    public function productVariation()
    {
        return $this->hasMany('devuelving\core\ProductVariationModel', 'product', 'id');
    }
    public function getUnitNameAttribute()
    {
        switch ($this->unit) {
            case 0:
                return 'KG';
                break;
            case 1:
                return 'litro';
                break;
            case 2:
                return 'unidad';
                break;
            case 3:
                return '100ml';
                break;
            case 4:
                return '100gr';
                break;
        }
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     * @author Aaron <aaron@devuelving.com>
     */
    public static function boot()
    {
        parent::boot();

        self::updating(function ($product) {
            // We check if the product belongs to a franchise
            if ($product->franchise === NULL) {
                // We check if the status of the product has changed
                if ($product->isDirty('discontinued')) {
                    // To prevent both clutter in the .rss and the database, we will only use one register per product
                    $productStatusUpdate = ProductStatusUpdatesModel::where('product', $product->id)->first();
                    if (!$productStatusUpdate) {
                        $productStatusUpdate = new ProductStatusUpdatesModel();
                    }
                    $productStatusUpdate->product = $product->id;
                    // Depending on the change, we inform the user one way or another through the .rss feed
                    if ($product->discontinued > 0) {
                        $productStatusUpdate->status = "El producto ha sido descatalogado";
                    } else {
                        $productStatusUpdate->status = "Producto añadido al catalogo de nuevo";
                    }
                    $productStatusUpdate->save();
                    // We only let the users know that the status have changed if the product is not discontinued
                } else if ($product->isDirty('unavailable') && $product->discontinued == 0) {
                    // To prevent both clutter in the .rss and the database, we will only use one register per product
                    $productStatusUpdate = ProductStatusUpdatesModel::where('product', $product->id)->first();
                    if (!$productStatusUpdate) {
                        $productStatusUpdate = new ProductStatusUpdatesModel();
                    }
                    $productStatusUpdate->product = $product->id;
                    // Depending on the change, we inform the user one way or another through the .rss feed
                    if ($product->unavailable > 0) {
                        $productStatusUpdate->status = "Stock agotado";
                    } else {
                        $productStatusUpdate->status = "Producto en stock de nuevo";
                    }
                    $productStatusUpdate->save();
                }
            }
        });
    }

    /**
     * Actualiza los precios del producto en la tabla de productos
     *
     * @since 3.0.0
     * @author Aaron <aaron@devuelving.com>
     * @return void
     */
    public function updatePrice()
    {
        if ($this->franchise === null) {
            // Obtenemos la regla del precio establecida
            if ($this->price_rules == 1) {
                $rule = 'asc';
            } else {
                $rule = 'desc';
            }
            // Obtenemos el product provider
            $productProvider = $this->getProductProvider();
            // Obtenemos el proveedor del producto
            $provider = $productProvider->getProvider();
            // Obtenemos el precio de coste y le sumamos el margen de beneficio del proveedor
            $costPrice = $productProvider->cost_price + ($productProvider->cost_price * ($provider->profit_margin / 100));
            // Apliquem recarrec a productes que volem vendre amb ports de franc (de moment nomes alfa y elektro3)
            // 19-01-22: Ara el recarrec el posem en un camp apart i no toquem el preu de cost.
            $freeShipping = 0;
            if ($provider->id == 7 || $provider->id == 15) {
                $costPriceIVA = $productProvider->cost_price * ($this->getTax() + 1);
                if ($costPriceIVA >= 150) {
                    //$freeShipping = 12.40;
                    $freeShipping = 15;
                    $this->transport = 1;
                } elseif ($costPriceIVA >= 80) {
                    //$freeShipping = 8.26;
                    $freeShipping = 10; // amb IVA
                    $this->transport = 1;
                } elseif ($costPriceIVA >= 40) {
                    //$freeShipping = 6.61;
                    $freeShipping = 8; //amb IVA
                    $this->transport = 1;
                } else {
                    $this->transport = 0;
                }
            }
            // Preu de venda franquiciat
            if ($provider->id == 5 || $provider->id == 6) {
                // Obtenemos el precio recomendado y le restamos el 10% que es el precio minimo de venta
                $default_price = $this->getRecommendedPrice() / 1.10;
            } else {
                // Obtenemos el precio de coste y le sumamos el beneficio del franquiciado por defecto
                $default_price = ($costPrice + ($productProvider->cost_price * ($provider->franchise_profit_margin / 100))) * ((TaxModel::find($this->tax)->value / 100) + 1);
            }
            // Actualizamos los precios de los productos
            $product = $this; //ProductModel::find($this->id);
            $oldCostPrice = $product->cost_price / (1 + $provider->profit_margin / 100);
            $product->cost_price = $costPrice;
            $product->default_price = $default_price;
            /*if ($provider->id != 5 && $provider->id != 6) {
                $product->free_shipping = $freeShipping;
            }*/
            $product->free_shipping = $freeShipping;
            $product->save();
            $newCostPrice = $product->cost_price / (1 + $provider->profit_margin / 100);
            // Comprobación de que el precio no es el mismo
            if (number_format($newCostPrice, 1) != number_format($oldCostPrice, 1)) {
                // Añadimos el registro de la nueva actualización del precio
                $this->addUpdatePrice($newCostPrice, $oldCostPrice);
            }
        }
    }

    /**
     * Añade una actualización del precio del producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param float $costPrice
     * @param float $oldCostPrice
     * @return void
     */
    public function addUpdatePrice($costPrice = 0, $oldCostPrice = 0)
    {
        // Obtenemos el anterior precio del producto
        try {
            $productPriceUpdate = DB::table('product_price_update')->where('product', $this->id)->orderBy('id', 'desc')->first();
            $oldType = $productPriceUpdate->type;
        } catch (\Exception $e) {
            // report($e);
            $oldType = 1;
        }
        // Obtenemos el tipo de actualización del precio del producto
        if ($oldCostPrice == 0 || ($oldType == 3 && ($this->unavailable == 0 || $this->discontinued == 0))) {
            $type = 1; // Nuevo producto
        } else if ($this->unavailable == 1 || $this->discontinued == 1) {
            $type = 3; // Eliminación producto
        } else {
            $type = 2; // Actualización del precio
        }
        // Comprobación de que el precio no es el mismo
        if (number_format($costPrice, 1) != number_format($oldCostPrice, 1)) {
            // Se añade un nuevo registro con el nuevo precio del producto
            DB::table('product_price_update')->insert([
                'product' => $this->id,
                'type' => $type,
                'new_price_cost' => $costPrice,
                'old_price_cost' => $oldCostPrice,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString()
            ]);
        }
    }

    /**
     * Función para obtener los datos de un producto
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
     * Función para obtener todas las imagenes de un producto ordenadas por preferencia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return array
     * @param $images ProductImageModel Parametro para controlar si viene del toArray en el frontend
     */
    public function getImages($images = null, $redirect = true)
    {
        $return = [];
        if ($images == null) {
            $images = $this->productImages ?: $this->productImages;
        }
        //$images = DB::table('product_image')->where('product', $this->id)->orderBy('default', 'desc')->get();

        foreach ($images as $image) {
            if ($redirect) {
                $return[] = route('index') . '/cdn/' . $image->image;
            } else {
                $return[] = config('app.cdn.url') . $image->image;
            }
        }
        if (count($return) < 1) {
            if ($redirect) {
                $return[] = route('index') . '/cdn/product/default.png';
            } else {
                $return[] = config('app.cdn.url') . 'default.png';
            }
        }
        return $return;
    }

    /**
     * Función para obtener la imagen destacada
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getDefaultImage($redirect = true)
    {
        // getMainImageAttribute
        $image = $this->main_image;
        
        if ($redirect) {
            if(empty($image)) {
                return route('index') . '/cdn/product/default.png';
            }
            return  route('index') . '/cdn/' . $image;
        }

        if(empty($image)) {
            return config('app.cdn.url') . '/default.png';
        }
        return config('app.cdn.url') . $image;
    }    

    /**
     * Función para obtener los ean del producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return array
     * @param $productProviders ProductProviderModel Parametro para controlar si viene del toArray en el frontend
     */
    public function getEan($productProviders = null)
    {
        $return = [];
        if ($productProviders == null)
            $productProviders = ProductProviderModel::where('product', $this->id)->groupBy("ean")->orderBy("id", "ASC")->limit(1)->get();

        foreach ($productProviders as $productProvider) {
            $return[] = $productProvider->ean;
        }
        return $return;
    }

    /**
     * Función para obtener el ean en un string
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function eanToString()
    {
        $return = '';
        foreach ($this->getEan() as $ean) {
            $return .= $ean . ' - ';
        }
        if (empty($return)) {
            $return = 'Sin EAN';
        } else {
            $return = substr($return, 0, -3);
        }
        return $return;
    }

    /**
     * Función para obtener la marca
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return BrandModel
     * @param $brand BrandModel Parametro para controlar si viene del toArray en el frontend
     */
    public function getBrand($brand = null)
    {
        if ($brand == null)
            return BrandModel::find($this->brand);
        else
            return $brand;
    }

    /**
     * Función para obtener el valor del iva de este producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getTax()
    {
        $tax = TaxModel::find($this->tax);
        return $tax->value / 100;
    }

    /**
     * Returns Provider for the product
     *
     * @param boolean $cheapest
     * @return void
     */
    public function getProvider($cheapest = false)
    {
        return $this->getProductProvider($cheapest)->getProvider();
    }

    /**
     * Función para obtene el producto proveedor
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param boolean $cheapest
     * @return void
     */
    public function getProductProvider($cheapest = false)
    {
        try {
            if ($this->franchise === null) {
                if (!$cheapest) {
                    if ($this->price_rules == 1) {
                        $rule = 'asc';
                    } else {
                        $rule = 'desc';
                    }
                } else {
                    $rule = 'asc';
                }
                if ($this->productProvider) {
                    $productProvider = $this->productProvider;
                    if(count($productProvider)>1){                        
                        foreach($productProvider as $providerData){
                            if($this->stock_type == $providerData->provider_data->type){
                                return $providerData;
                            }
                        }
                    }else{
                        return $productProvider->first();
                    }                    
                } else {
                    $productProvider = ProductProviderModel::join('provider', 'product_provider.provider', '=', 'provider.id');
                    $productProvider->where('product_provider.product', $this->id);
                    $productProvider->where('provider.active', 1);
                    /*$productProvider->orderBy('product_provider.cost_price', $rule);
                    $productProvider->select('product_provider.*', 'provider.name');
                    $provider = $productProvider->first();*/

                    /*if($this->hasPhysicalStock()){
                        $productProvider->where('provider.stock_type', config('settings.stock_types.fisico'));
                    }*/
                    $productProvider->orderBy('product_provider.cost_price', $rule);
                    $productProvider->select('product_provider.*', 'provider.name');
                }
                $provider = $productProvider->first();
            } else {
                if (!$cheapest) {
                    $rule = 'desc';
                } else {
                    $rule = 'asc';
                }
                if ($this->stock_type == config('settings.stock_types.fisico'))
                    $provider = ProductStockModel::where('product', $this->id)->orderBy('product_stock.purchase_price', $rule)->first();
                else
                    $provider = ProductProviderModel::where('product', $this->id)->orderBy('product_provider.cost_price', $rule)->first();
            }
            return $provider;
        } catch (\Exception $e) {
            // report($e);
            return null;
        }
    }

    /**
     * Función para obtener datos de producto proveedor
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param string $data
     * @param boolean $cheapest
     * @return void
     */
    public function getProductProviderData($data, $cheapest = false)
    {
        try {
            $productProvider = $this->getProductProvider($cheapest);
            return $productProvider->$data;
        } catch (\Exception $e) {
            // report($e);
            return null;
        }
    }

    /**
     * Función para obtener el precio de coste
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param boolean $tax
     * @return void
     */
    public function getPublicPriceCost($tax = true)
    {
        // cuando es demo y el usuario es diferente de demo@devuelving.com no tiene descuentos
        if (FranchiseModel::getFranchise() && FranchiseModel::getFranchise()->type == 0 && auth()->user() && auth()->user()->type != 1) {
            $discount = 1;
        } else {
            //info('paso por getDiscountTarget() para producto'.$this->id);
            $discount = $this->getDiscountTarget();
        }
        $cost_price = $this->cost_price * $discount;
        if ($this->transport == 1 && FranchiseModel::custom('free_transport', true)) {
            $cost_price = $cost_price + $this->free_shipping;
        }
        if ($tax) {
            //info('paso por getTax()');
            return $cost_price * ($this->getTax() + 1);
        } else {
            //info('paso por Tax null');
            return $cost_price;
        }
    }

    /**
     * Función para comprobar si tienen activos algún descuento y aplicarlo al precio de coste
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getDiscountTarget()
    {
        $discount = 1;
        $franchise = FranchiseModel::getFranchise();
        //return $discount;
        if ($franchise && !$this->franchise) {
            try {
                // Comprobamos si la franquicia tiene los descuentos activados
                $franchise_discount = $franchise->getCustom('discount');

                if ($franchise_discount != null) {
                    //info('hola, estic a getDiscountTarget()  '.$this->id);
                    $franchiseDiscounts = json_decode($franchise_discount);
                    // Recorremos todos los descuentos de la franquicia                 
                    foreach ($franchiseDiscounts as $FranchiseDiscountTarget) {
                        // Obtenemos los datos de los descuentos
                        $discountTarget = DiscountTargetsModel::find($FranchiseDiscountTarget);
                        $target = json_decode($discountTarget->target);
                        // Comprobamos si el descuento es de tipo 1, lo que significa que el id del producto esta en los datos del descuento
                        if ($discountTarget->type == 1) {
                            if (in_array($this->id, $target)) {
                                $discount = 1 - ($discountTarget->discount / 100);
                            }
                        // Comprobamos si el descuento es de tipo 2, lo que significa que se aplica un descuento por proveedor
                        } else if ($discountTarget->type == 2) {
                            if (in_array($this->getProductProvider()->provider, $target)) {
                                $discount = 1 - ($discountTarget->discount / 100);
                            }
                        // Comprobamos si el descuento es de tipo 3, que se aplica por franja de precio
                        }else if ($discountTarget->type == 3) {
                            foreach ($target as $target_discount) {                                
                                if($target_discount->price_min < $this->cost_price && $target_discount->price_max > $this->price_cost){
                                    //info('product->'.$this->id.' | '.$target_discount->price_min. ' | '. $this->cost_price.' | ' .$target_discount->price_max.' | '.$target_discount->discount);
                                    $discount = 1 - ($target_discount->discount / 100);
                                }
                            }
                            
                        }
                    }
                }
            } catch (\Exception $e) {
                report($e);
            }
        }
        return $discount;
    }
    /**
     * Función para calcular el número de unidades compradas de un producto para un usuario concreto
     *     
     */
    public function getUnitsPurchased($user = null)
    {
        //select sum(order_details.units) from `orders` 
        //inner join `order_details` on `orders`.`id` = `order_details`.`order` 
        //inner join `product` on `product`.`id` = `order_details`.`product` 
        //where `orders`.`customer` = 1 and `orders`.`status` not in (10, 0, 1) and `order_details`.`product` = 38349
        $unitsPurchased = OrderDetailModel::join('orders', 'order_details.order', '=', 'orders.id')
            ->join('product', 'product.id', '=', 'order_details.product')
            ->where('orders.customer', $user)
            ->whereNotIn('orders.status', [0, 1, 10])
            ->where('order_details.product', '=', $this->id)
            ->select('order_details.units')
            ->sum('order_details.units');
        return $unitsPurchased;
    }
    /**
     * Función para obtener el precio de coste sin IVA
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getPublicPriceCostWithoutIva()
    {
        return $this->getPublicPriceCost(false);
        
    }

    /**
     * Función para obtener el precio sin IVA
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getPriceWithoutIva()
    {
        $price = $this->getPrice(7);
        if ($price != null) {
            return $price;
        }
        return null;
    }

    /**
     * Función para obtener el precio con el margen de beneficio del proveedor anterior al cambio
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getOldPublicPriceCost()
    {
        $productPriceUpdate = DB::table('product_price_update')->where('product', $this->id)->orderBy('id', 'desc')->first();
        return $productPriceUpdate->price + ($productPriceUpdate->price * $this->getTax());
    }

    /**
     * Función para obtener la fecha de la ultima actualización de los precios de un producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getLastPriceUpdate()
    {
        if ($this->getProductProviderData('cost_price') != null) {
            $productPriceUpdate = DB::table('product_price_update')->where('product', $this->id)->orderBy('id', 'desc')->first();
            return $productPriceUpdate->created_at;
        }
        return null;
    }

    /**
     * Función para obtener el precio recomendado
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getRecommendedPrice()
    {
        return $this->recommended_price;
    }

    /**
     * Función para comprobar si tiene un precio customizado
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function checkCustomPrice()
    {
        if (!empty($this->productCustoms) && $this->productCustoms->price !== null) {
            return true;
        } else {
            return false;
        }
        $franchise = FranchiseModel::getFranchise();
        $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', $franchise->id)->whereNotNull('price')->get();
        if (count($productCustom) == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Función para comprobar el tipo de precio custom del precio
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function typeCustomPrice()
    {        
        if (!empty($this->productCustoms) && $this->productCustoms->price !== null) {
            if ($this->productCustoms->price_type == 1) {
                return 1;
            } else if ($this->productCustoms->price_type == 2) {
                return 2;
            }
        } else {
            return false;
        }
        $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', FranchiseModel::getFranchise()->id)->whereNotNull('price')->first();
        if (empty($productCustom)) {
            return 0;
        } else {
            if ($productCustom->price_type == 1) {
                return 1;
            } else if ($productCustom->price_type == 2) {
                return 2;
            }
        }
    }
    /**
     * Función para comprobar el %
     *
     * @since 3.0.0
     * @return void
     */
    public function marginCustomPrice()
    {
        if (!empty($this->productCustoms) && $this->productCustoms->price !== null) {
            if ($this->productCustoms->price_type == 1) {
                $pmv = $this->getPMV();
                $margin_custom_price = ($this->productCustoms->price - $pmv) * 100 /$pmv;
                return $margin_custom_price;
            } else if ($this->productCustoms->price_type == 2) {
                return $this->productCustoms->price;
            }
        } else {
            $pmv = $this->getPMV();
            $margin_custom_price = ($this->default_price - $pmv) * 100 /$pmv;
            return $margin_custom_price;
        }
        
    }

    /**
     * Función para comprobar si el producto esta en promocion
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return boolean
     * @param $productCustom ProductCustomModel Parametro para controlar si viene del toArray en el frontend
     */
    public function checkPromotion($productCustom = null)
    {

        if (!empty($this->productCustoms) && $this->productCustoms->promotion === 1) {
            return true;
        }
        return false;
        /*
        if ($productCustom == null)
            $productCustom = ProductCustomModel::where('franchise', FranchiseModel::getFranchise()->id)->where('product', $this->id)->whereNotNull('promotion');
        else
            $productCustom->where('promotion', '!=', NULL)->where('franchise', FranchiseModel::getFranchise()->id);
        if ($productCustom->count() == 0) {
            return false;
        } else {
            return true;
        }*/
    }
    /** 
     * Función para comprobar si el producto esta oculto para la franquicia
     *    
     * @return boolean
     * @param $productCustom ProductCustomModel Parametro para controlar si viene del toArray en el frontend
     */
    public function checkRemoved($productCustom = null)
    {

        if (!empty($this->productCustoms) && $this->productCustoms->removed === 1) {
            return true;
        }
        return false;
    }
    /**
     * Función para comprobar si el producto se envia a Canarias
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function checkCanarias()
    {
        if ($this->shipping_canarias == 1) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Comprueba si el producto esta en promociones por defecto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function checkSuperPromo()
    {
        if ($this->promotion == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Comprueba que el producto esta en liquidación
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function checkLiquidation()
    {
        if ($this->liquidation == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Comprobamos si los productos tiene la oferta 2x1
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function checkDoubleUnit()
    {
        if ($this->double_unit == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Función para obtener el precio de venta al publico
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getPrice($call = null,$check_techno = null,$product_variation=null)
    {
        //info('ProductModel@getPrice() call->'.$call. ' para el producto ' . $this->id.' variación:'.$product_variation);
        $price = 0;
        $franchise = FranchiseModel::getFranchise();
        if($product_variation){
            
            foreach($this->productVariation as $product_variation_item){
                if($product_variation_item->id == $product_variation)
                    $price = $product_variation_item->price;
            }
        /*03/09/2024 Siscu: amplio casuísticas para premium,  pendiente de validar    */        
        //}else if ($franchise->type == 0 && $check_techno){  
        //    $price = $this->productProvider->pluck('cost_price')[0];
        }else if ($franchise->type == 1 && auth()->user() && auth()->user()->type == 3 && auth()->user()->premium && $franchise->getServices('premium')){
            $price = $this->getPMV();
        }else if (($this->highlight != 1 && ($franchise->type == 0 || $franchise->type == 3))
            || (auth()->user() && auth()->user()->type == 3 && auth()->user()->premium && $franchise->getServices('premium'))){
            $price = $this->getPublicPriceCost();  
        }else{
            if ($this->checkCustomPrice()) {
                $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', $franchise->id)->first();
                if ($productCustom->price_type == 1) {
                    $price = $productCustom->price;
                } else {
                    $price = $this->getPMV() * (($productCustom->price / 100) + 1);
                }
            } else {
                $price = $this->default_price;
                // 20-01-2022: Que calculi dinamicament el preu quan transport sigui 1.
                if ($this->transport == 1 && $franchise->custom('free_transport', true)) {
                    $price = $price + $this->free_shipping;
                }
            }
        }
        return $price;
    }

    /**
     * Función para obtener las categorias del producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getCategories()
    {
        return ProductCategoryModel::where('product', $this->id)->get();
    }

    /**
     * Función para obtener el beneficio de un producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getProfit($price = null)
    {
        info('getprofit(price) '.$price);
        if(!$price){
            $price = $this->getPrice(8);
            info('getprofit() '.$price);
        }
        
        if ($price != null) {
            $publicprice = $this->getPublicPriceCost();
            return ($price - $publicprice) / $publicprice;
        }
        return 0;
    }

    /**
     * Función para obtener el margen beneficio real del producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param boolean $front
     * @return void
     */
    public function getProfitMargin($price = false,$front = false)
    {
        info('getProfitMargin() '.$price);
        $profit = $this->getProfit($price);
        info('getProfitMargin->getprofit  '.$profit);
        if ($profit != null) {
            if ($front == false) {
                return ($profit * 100);
            } else {
                if (($profit * 100) > $this->getFullPriceMargin()) {
                    return 0;
                } else {
                    return ($profit * 100);
                }
            }
        }
        return 0;
    }

    /**
     * Función para obtener el descuento entre el precio de venta y el PVPR
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getPublicMarginProfit($publicPrice = null)
    {
        //info('llego a getPublicMarginProfit para el producto #'.$this->id.'//'.$this->name.' con precio de venta '.$publicPrice.' y pvpr '.$this->recommended_price);
        try {
            if (!$publicPrice) {
                $publicPrice = $this->getPrice(9);
            }

            $recommendedPrice = $this->getRecommendedPrice();
            return round((($recommendedPrice - $publicPrice) / $recommendedPrice) * 100);
        } catch (\Exception $e) {
            // report($e);
            return 0;
        }
    }

    /**
     * Función para obtener el beneficio entre el precio de coste y el pvpr
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getFullPriceMargin()
    {
        if ($this->getRecommendedPrice() != null) {
            $recommendedPrice = $this->getRecommendedPrice();
            $costPrice = $this->getPublicPriceCost();
            return round((($recommendedPrice - $costPrice) / $costPrice) * 100);
        }
        return null;
    }

    /**
     * Función para añadir custom (precio,promotion,removed) al producto para la franquicia
     *
     * @since 3.0.0
     * @author Soporte
     * @param array $options
     * @return boolean
     */
    public function productCustom($options = [])
    {
        //$productCustom = ProductCustomModel::where('franchise', FranchiseModel::getFranchise()->id)->where('product', $this->id)->first();        
        //if (empty($productCustom)) {
        if(empty($this->productCustoms)){
            info('sin custom');
            $productCustom = new ProductCustomModel();
            $productCustom->product = $this->id;
            $productCustom->franchise = FranchiseModel::getFranchise()->id;
        }else{
            info('con custom');
            $productCustom = $this->productCustoms;
        }
        switch ($options['action']) {
            case 'price':
                $status = false;
                $newPrice = 0;
                $pmv = 0;
                
                $tax = $this->productTax->value/100;
                
                $pmv = $this->getPMV();  //obtenemos el precio mínimo de venta para el producto con iva incluido  
                
                if ($this->transport == 1 && FranchiseModel::custom('free_transport', true)) {
                    $costPrice = ($this->cost_price + $this->free_shipping) * ($tax + 1) ;
                } else {
                    $costPrice = $this->cost_price * ($tax + 1);
                }
                
                $productPriceCost = $this->getPublicPriceCostWithoutIva();
                
                $productPriceCostTax = $productPriceCost * ($tax + 1);
                /*
                $costPrice = $productPriceCostTax;//$this->getPublicPriceCost();
                */
                
                if ($options['price'] == null || $options['price_type'] == null) {
                    $productCustom->price = null;
                    $productCustom->price_type = null;
                    $status = true;
                    $message = 'Precio actualizado correctamente';
                } else {
                    $provider = $this->productProvider->first()->provider;//$this->getProductProviderData('provider');
                    $defaultprice = $this->default_price;

                    if($options['price_type'] == 1){
                        $newPrice = $options['price'];
                    }
                    if($options['price_type'] == 2){
                        if ($provider == 5 || $provider == 6){
                            $newPrice = $pmv  + ($pmv * ($options['price'] / 100));
                        }else{
                            $newPrice = $costPrice  + ($costPrice * ($options['price'] / 100));
                        }
                        
                    }
                   
                    
                    
                                   
                    if($newPrice < $pmv){
                        if ($provider == 5 || $provider == 6){
                            $status = false;
                            $message = 'Condiciones especiales para este proveedor. Descuento máximo sobre PVPR del 10%.';                        

                        }else{
                            $status = false;
                            $message = 'El precio debe ser mayor que el precio mínimo de venta (PMV)'; 
                        }

                    }else{
                        $productCustom->price = number_format($options['price'], 2, '.', '');
                        $productCustom->price_type = $options['price_type'];
                        $status = true;
                        $message = 'Precio actualizado correctamente'; 
                    }

                }
                info('id: '.$this->id.' new price: ' . $newPrice .' pmv: ' . $pmv.' status'.$status. ' price type: ' . $options['price_type'].' request number: ' . number_format($options['price'], 2, '.', ''));
                $custom_price = false;
                if($status){
                    $productCustom->save();
                    $custom_price = true;
                }
                
                return [
                    'status' => $status,
                    'message' => $message,
                    'custom_price' => $custom_price,//$this->checkCustomPrice(),
                    'type_custom_price' => $productCustom->price_type ?? false,
                    'cost_price' => number_format($productPriceCost, 2, '.', ''),
                    'cost_price_iva' => number_format($productPriceCostTax, 2, '.', ''),
                    'price' => number_format($productCustom->price, 2, '.', ''),
                    /*
                    'recommended_price' => number_format($this->recommended_price, 2, '.', ''),
                    'price' => number_format($this->getPrice(), 2, '.', ''),
                    'profit_margin' => $this->getProfitMargin(),
                    'full_price_margin' => $this->getFullPriceMargin(),
                    */
                ];
                break;
            case 'promotion':
                $productCustom->promotion = $options['promotion'];
                $productCustom->save();
                if ($options['promotion'] == 1) {
                    return [
                        'status' => true,
                        'message' => 'El producto se ha añadido a promociones correctamente'
                    ];
                } else {
                    return [
                        'status' => true,
                        'message' => 'El producto se ha quitado de promociones correctamente'
                    ];
                }
                break;
            case 'removed':
                $productCustom->removed = $options['removed'];
                $productCustom->save();
                if ($options['removed'] == 1) {
                    return [
                        'status' => true,
                        'message' => 'El producto se ha ocultado correctamente'
                    ];
                } else {
                    return [
                        'status' => true,
                        'message' => 'El producto se ha activado correctamente'
                    ];
                }
                break;
            default:
                return [
                'status' => false,
                'message' => 'No se ha enviado una acción válida'
            ];    
            
        }
    }

    /**
     * Función para obtener el nombre del producto segun la franquicia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return string
     * @param $productCustom ProductCustomModel Parametro para controlar si viene del toArray en el frontend
     */
    public function getName($productCustom = null, $variation = null)
    {
        //info('producto '.$this->id.'  variación  '.$variation);
        if (!empty($this->productCustoms) && $this->productCustoms->name !== null) {
            return $this->productCustoms->name;
        }
        if($variation){
            $product_variation = ProductVariationModel::where('product',$this->id)->where('id', $variation)->first();
            $variation_type = json_decode($this->variations);//DB::table('variation')->where('id', $this->variations)->first();
            return $this->name. ' '. $variation_type->name. ' '.$product_variation->name;
        }
        return $this->name;

        /*
        if ($productCustom == null)
            $productCustom = ProductCustomModel::where('franchise', FranchiseModel::getFranchise()->id)->where('product', $this->id)->first();

        if (empty($productCustom)) {
            return $this->name;
        } else {
            if (isset($productCustom->name) && $productCustom->name != null) {
                return $productCustom->name;
            } else {
                return $this->name;
            }
        }
        */
    }

    /**
     * Función para obtener la descripción del producto segun la franquicia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     * @param $productCustom ProductCustomModel Parametro para controlar si viene del toArray en el frontend
     */
    public function getDescription($productCustom = null)
    {
        if (!empty($this->productCustoms) && $this->productCustoms->description !== null) {
            return $this->productCustoms->description;
        }
        return $this->description;
        /*
        if ($productCustom == null)
            $productCustom = ProductCustomModel::where('franchise', FranchiseModel::getFranchise()->id)->where('product', $this->id)->first();

        if (empty($productCustom)) {
            return $this->description;
        } else {
            if (isset($productCustom->description) && $productCustom->description != null) {
                return $productCustom->description;
            } else {
                return $this->description;
            }
        }
        */
    }

    /**
     * Función para obtener el slug del producto segun la franquicia
     *
     * @since 
     * @author 
     * @return string
     */
    public function getSlug()
    {
        if (!empty($this->productCustoms) && $this->productCustoms->slug !== null) {
            return $this->productCustoms->slug;
        }
        return $this->slug;
    }
    
    /**
     * Método para obtener la descripción corta
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param integer $maxLength
     * @return void
     */
    public function getShortDescription($maxLength = 440)
    {
        $string = strip_tags($this->getDescription());
        $substring = substr($string, 0, $maxLength);
        if (strlen($string) > strlen($substring)) {
            return $substring . '...';
        } else {
            return $substring;
        }
    }

    /**
     * Función para obtener las etiquetas meta personalizadas
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param string $type
     * @return void
     */
    public function getMetaData($type)
    {
        $productCustom = ProductCustomModel::where('franchise', FranchiseModel::getFranchise()->id)->where('product', $this->id)->first();
        if (empty($productCustom)) {
            if ($type == 'meta_title') {
                return $this->getName();
            } else if ($type == 'meta_description') {
                return $this->getShortDescription(250);
            } else if ($type == 'meta_keywords') {
                return null;
            }
        } else {
            if ($type == 'meta_title') {
                if ($productCustom->meta_title != null) {
                    return $productCustom->meta_title;
                } else {
                    return $this->getName();
                }
            } else if ($type == 'meta_description') {
                if ($productCustom->meta_description != null) {
                    return $productCustom->meta_description;
                } else {
                    return $this->getShortDescription(250);
                }
            } else if ($type == 'meta_keywords') {
                if ($productCustom->meta_keywords != null) {
                    return $productCustom->meta_keywords;
                } else {
                    return null;
                }
            }
        }
    }

    /**
     * Devuelve stock actual, true si no mantenemos stock o false si está agotado.
     *
     * @since 3.0.0
     * @author Aaron Bujalance <aaron@devuelving.com>
     * @return boolean
     */
    public function getStock($order = 0, $variation = null)
    {
        
        if (!$this->unavailable && !$this->discontinued) {
            if ($this->hasPhysicalStock()) { //stock type 1
                $additions = ProductStockModel::where('product_stock.type', '=', 2)->where('product_stock.product', '=', $this->id)->sum('stock');
                $subtractions = ProductStockModel::where('product_stock.type', '=', 1)->where('product_stock.product', '=', $this->id)->sum('stock');
                $stock = $additions - $subtractions;
                /*
                $stockb = ProductStockModel::selectRaw( 'product, IF(type = 2) sum(stock) as entradas, sum(IF(type =1, stock, 0)) as salidas, sum(IF(type < 2, stock*-1, stock)) as result')
                ->where('product_stock.product', '=', $this->id)
                ->groupBy ('product')->get();
                */

                if ($stock < 0) $stock = 0;
                //info('getStock-->  '.$this->id. '  stock-> '.$stock . '  stockB-> '.print_r($stockb,true));
                return $stock;
            } else if ($this->hasDropshippingStock()) { //stock type 3
                /**/
                if($variation){
                    
                    //$this->productVariation->filter(function ('variation', $variation) {return $value > 2;});
                    foreach($this->productVariation as $productvariation){
                        if($variation == $productvariation->id) $stock = $productvariation->stock;
                    }
                    info($stock.' de stock para variación '. $variation);
                }else{
                    $stock = $this->getProductProvider()->stock;
                }
                //$stock = $this->getProductProvider()->stock;

                $date = Carbon::now()->subDays(2)->toDateString();
                $reserved = OrderDetailModel::join('orders', 'order_details.order', '=', 'orders.id')
                    ->where('product', $this->id)
                    ->where('order', '!=', $order)
                    ->whereDate('orders.created_at', '>=', $date, ' and')
                    ->whereIn('orders.status', [1, 2]);
                $reserved->where(function ($query) use ($date) {
                    $query->where('orders.payment_method', '!=', 6);
                    $query->orWhereNotNull('orders.payment_date');
                    $query->orWhere('orders.status', 2);
                });
                //info('getStock-->  '.$this->id. '  stock-> '.$stock);
                return $stock - ($reserved->sum('order_details.units'));
            } else if ($this->hasLiquidationStock()) {
                return $this->getProductProvider()->stock;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Método para comprobar si se muestran los precios visibles
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return boolean
     */
    public function visiblePrice()
    {
        if ((bool) FranchiseModel::custom('visible_price', false) || auth()->check()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Método para comporbar si se muetran los descuentos
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return boolean
     */
    public function visibleDiscounts($publicPrice = null)
    {

        if (!$publicPrice) {
            $publicPrice = 0;
            $PublicMarginProfit = $this->getPublicMarginProfit();
        } else {
            $PublicMarginProfit = $this->getPublicMarginProfit($publicPrice);
        }
        //info('Producto '.$this->name.'//precioventa: '.$publicPrice. '//descuento margen '.$PublicMarginProfit);

        if (((FranchiseModel::getFranchise()->type == 0) && ($PublicMarginProfit < 25)) || (!(bool) FranchiseModel::custom('visible_discounts', true)) || $PublicMarginProfit < 5) {
            //info('en visible discounts return false');
            return false;
        } else {
            //info('en visible discounts return true');
            return true;
        }
    }

    /**
     * Función para imprimir un banner del producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function print($productdata = null, $check_smartphone = false, $check_techno = false)
    {
        $product = $this; //ProductModel::find($this->id);
        if (!$productdata) {
            $productdata = [];
        }
        return view('modules.catalog.product', compact('product', 'productdata','check_smartphone','check_techno'));
    }


    public function hasPhysicalStock()
    {
        return $this->stock_type == config('settings.stock_types.fisico');
    }

    public function hasDropshippingStock()
    {
        return $this->stock_type == config('settings.stock_types.dropshipping');
    }

    public function hasLiquidationStock()
    {
        return $this->stock_type == config('settings.stock_types.liquidacion');
    }

    public function getDiscount($franchise)
    {
        $discount = 1;
        if ($franchise) {
            try {
                // Comprobamos si la franquicia tiene los descuentos activados
                // $franchise_discount = $franchise->getCustom('discount');
                $franchise_discount = FranchiseCustomModel::where('franchise', $franchise->id)->where('var', 'discount')->first(['value']);
                if ($franchise_discount) {
                    $franchiseDiscounts = json_decode($franchise_discount->value);
                    // Recorremos todos los descuentos de la franquicia  
                    foreach ($franchiseDiscounts as $FranchiseDiscountTarget) {
                        // Obtenemos los datos de los descuentos
                        $discountTarget = DiscountTargetsModel::find($FranchiseDiscountTarget);
                        // Comprobamos si el descuento es de tipo 1, lo que significa que el id del producto esta en los datos del descuento
                        if (isset($discountTarget)) {
                            $target = json_decode($discountTarget->target);
                            if ($discountTarget->type == 1) {
                                if (in_array($this->id, $target)) {
                                    $discount = 1 - ($discountTarget->discount / 100);
                                }
                                // Comprobamos si el descuento es de tipo 2, lo que significa que se aplica un descuento por proveedor
                            } else if ($discountTarget->type == 2) {
                                if (in_array($this->getProvider()->id, $target)) {
                                    $discount = 1 - ($discountTarget->discount / 100);
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                report($e);
            }
        }
        return $discount;
    }
     /**
     * Obtiene precio por defecto del producto 
     *
     * @since 3.0.0
     * @author <soporte@devuelving.com>
     * @return void
     */
    public function getDefaultPrice()
    {
        if($this->id){
            $defaultprice = $this->default_price;
        }else {
            $defaultprice = Product::find($this->id);
            $defaultprice = $defaultprice->default_price;
        }
        
        return $defaultprice;
    }
    /**
     * Obtiene el precio mínimo de venta
     *
     * @since 3.0.0
     * @author <soporte@devuelving.com>
     * @return void
     */
    public function getPMV()
    {
        /*
        if($this->productProvider){
            $productProvider = $this->productProvider->first();
            if($productProvider->provider == 5 || $productProvider->provider == 6){
                $pmv = $this->recommended_price - ($this->recommended_price * 0.10);;
            }else{
                $pmv = $this->cost_price * (($this->productTax->value/100) + 1);
            }
        }else{
           $pmv = $this->cost_price * (($this->productTax->value/100) + 1); 
        }
        return $pmv;*/
        if($this->productProvider){
            $productProvider = $this->productProvider->first();
            if ($this->transport == 1 && FranchiseModel::custom('free_transport', true)) {
                $pmv = ($this->cost_price + $this->free_shipping) * (($this->productTax->value/100) + 1) ;
            } else {
                if($productProvider->provider == 5 || $productProvider->provider == 6){
                    $pmv = $this->recommended_price - ($this->recommended_price * 0.10);
                } else {
                    $pmv = $this->cost_price * (($this->productTax->value/100) + 1);
                }
            }
        }else{
           $pmv = $this->cost_price * (($this->productTax->value/100) + 1); 
        }
        return $pmv;
    }
    
}
