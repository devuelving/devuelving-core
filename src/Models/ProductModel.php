<?php

namespace devuelving\core;

use Carbon\Carbon;
use devuelving\core\TaxModel;
use devuelving\core\BrandModel;
use devuelving\core\ProductModel;
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
        'slug', 'name', 'description', 'stock_type', 'minimum_stock', 'transport', 'weight', 'volume', 'tax', 'brand', 'tags', 'variations', 'franchise', 'promotion', 'free_shipping', 'double_unit', 'units_limit', 'liquidation', 'unavailable', 'discontinued', 'external_sale', 'highlight', 'price_edit', 'shipping_canarias', 'cost_price', 'recommended_price', 'default_price', 'profit_margin', 'franchise_profit_margin', 'price_rules', 'meta_title', 'meta_description', 'meta_keywords',
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
    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    /**
     * Actualiza los precios del producto en la tabla de productos
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function updatePrice()
    {
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
        if ($provider->id == 5 || $provider->id == 6) {
            // Obtenemos el precio recomendado y le restamos el 10% que es el precio minimo de venta
            $default_price = $this->getRecommendedPrice() / 1.10;
        } else {
            // Obtenemos el precio de coste y le sumamos el beneficio del franquiciado por defecto
            $default_price = ($costPrice + ($productProvider->cost_price * ($provider->franchise_profit_margin / 100))) * ((TaxModel::find($this->tax)->value / 100) + 1);
        }
        // Actualizamos los precios de los productos
        $product = ProductModel::find($this->id);
        $oldCostPrice = $product->cost_price;
        $product->cost_price = $costPrice;
        $product->default_price = $default_price;
        $product->save();
        // Comprobación de que el precio no es el mismo
        if (number_format($costPrice, 1) != number_format($oldCostPrice, 1)) {
            // Añadimos el registro de la nueva actualización del precio
            $this->addUpdatePrice($costPrice, $oldCostPrice);
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
     * @return void
     */
    public function getImages()
    {
        $return = [];
        $images = DB::table('product_image')->where('product', $this->id)->orderBy('default', 'desc')->get();
        foreach ($images as $image) {
            $return[] = config('app.cdn.url') . $image->image;
        }
        if (count($return) < 1) {
            $return[] = config('app.cdn.url') . 'default.png';
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
    public function getDefaultImage()
    {
        return $this->getImages()[0];
    }

    /**
     * Función para obtener los ean del producto
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getEan()
    {
        $return = [];
        $productProviders = ProductProviderModel::where('product', $this->id)->get();
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
     * @return void
     */
    public function getBrand()
    {
        return BrandModel::find($this->brand);
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
                $productProvider = ProductProviderModel::join('provider', 'product_provider.provider', '=', 'provider.id');
                $productProvider->where('product_provider.product', $this->id);
                $productProvider->where('provider.active', 1);
                $productProvider->orderBy('product_provider.cost_price', $rule);
                $productProvider->select('product_provider.*', 'provider.name');
                $productProvider = $productProvider->first();
            } else {
                if (!$cheapest) {
                    $rule = 'desc';
                } else {
                    $rule = 'asc';
                }
                $productProvider = ProductProviderModel::where('product', $this->id)->orderBy('product_provider.cost_price', $rule)->first();
            }
            return $productProvider;
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
        if ($tax) {
            return ($this->cost_price * $this->getDiscountTarget()) * ($this->getTax() + 1);
        } else {
            return ($this->cost_price * $this->getDiscountTarget());
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
        if (FranchiseModel::getFranchise()) {
            try {
                // Comprobamos si la franquicia tiene los descuentos activados
                if (FranchiseModel::getFranchise()->getCustom('discount') != null) {
                    $franchiseDiscounts = json_decode(FranchiseModel::getFranchise()->getCustom('discount'));
                    // Recorremos todos los descuentos de la franquicia
                    foreach ($franchiseDiscounts as $FranchiseDiscountTarget) {
                        // Obtenemos los datos de los descuentos
                        $discountTarget = DiscountTargetsModel::find($FranchiseDiscountTarget);
                        $target = json_decode($discountTarget->target);
                        // Comprobamos si el descuento es de tipo 1, lo que significa que el id del producto esta en los datos del descuento
                        if ($discountTarget->type == 1) {
                            if (in_array($this->id, $target)) {
                                $discount = 1 - ($discountTarget->discount/100);
                            }
                        // Comprobamos si el descuento es de tipo 2, lo que significa que se aplica un descuento por proveedor
                        } else if ($discountTarget->type == 2) {
                            if (in_array($this->getProvider()->id, $target)) {
                                $discount = 1 - ($discountTarget->discount/100);
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
        if ($this->getPrice() != null) {
            return $this->getPrice();
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
        $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', FranchiseModel::get('id'))->whereNotNull('price')->get();
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
        $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', FranchiseModel::get('id'))->whereNotNull('price');
        if ($productCustom->count() == 0) {
            return 0;
        } else {
            $productCustom = $productCustom->first();
            if ($productCustom->price_type == 1) {
                return 1;
            } else if ($productCustom->price_type == 2) {
                return 2;
            }
        }
    }

    /**
     * Función para comprobar si el producto esta en promocion
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function checkPromotion()
    {
        if (ProductCustomModel::where('product', $this->id)->where('franchise', FranchiseModel::get('id'))->whereNotNull('promotion')->count() == 0) {
            return false;
        } else {
            return true;
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
    public function getPrice()
    {
        $price = 0;
        if ($this->checkCustomPrice()) {
            $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', FranchiseModel::get('id'))->first();
            if ($productCustom->price_type == 1) {
                $price = $productCustom->price;
            } else {
                $price = $this->getPublicPriceCost() * (($productCustom->price / 100) + 1);
            }
        } else {
            $price = $this->default_price;
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
    public function getProfit()
    {
        if ($this->getPrice() != null) {
            return ($this->getPrice() - $this->getPublicPriceCost()) / $this->getPublicPriceCost();
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
    public function getProfitMargin($front = false)
    {
        if ($this->getProfit() != null) {
            if ($front == false) {
                return round($this->getProfit() * 100);
            } else {
                if (($this->getProfit() * 100) > $this->getFullPriceMargin()) {
                    return 0;
                } else {
                    return round($this->getProfit() * 100);
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
    public function getPublicMarginProfit()
    {
        try {
            $publicPrice = $this->getPrice();
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
     * Función para poner la unidad customizada al producto para la franquicia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @param array $options
     * @return void
     */
    public function productCustom($options = [])
    {
        $productCustom = ProductCustomModel::where('franchise', FranchiseModel::get('id'))->where('product', $this->id);
        if ($productCustom->count() == 0) {
            $productCustom = new ProductCustomModel();
            $productCustom->product = $this->id;
            $productCustom->franchise = FranchiseModel::get('id');
        } else {
            $productCustom = $productCustom->first();
        }
        if ($options['action'] == 'price') {
            if ($options['price'] == null || $options['price_type'] == null) {
                $productCustom->price = null;
                $productCustom->price_type = null;
            } else {
                $publicPrice = $options['price'];
                $costPrice = $this->getPublicPriceCost();
                $margin = round((($publicPrice - $costPrice) / $costPrice) * 100);
                if ($margin < 1 && $options['price_type'] == 1) {
                    return [
                        'status' => false,
                        'message' => 'El precio tiene que tener un beneficio minimo de un 1%',
                        'custom_price' => $this->checkCustomPrice(),
                        'type_custom_price' => $this->typeCustomPrice(),
                        'cost_price' => number_format($this->getPublicPriceCostWithoutIva(), 2, '.', ''),
                        'cost_price_iva' => number_format($this->getPublicPriceCost(), 2, '.', ''),
                        'recommended_price' => number_format($this->getRecommendedPrice(), 2, '.', ''),
                        'price' => number_format($this->getPrice(), 2, '.', ''),
                        'profit_margin' => $this->getProfitMargin(),
                        'full_price_margin' => $this->getFullPriceMargin(),
                    ];
                } else {
                    $productCustom->price = number_format($options['price'], 2, '.', '');
                    $productCustom->price_type = $options['price_type'];
                }
            }
            $productCustom->save();
            return [
                'status' => true,
                'message' => 'Se ha actualizado el precio correctamente',
                'custom_price' => $this->checkCustomPrice(),
                'type_custom_price' => $this->typeCustomPrice(),
                'cost_price' => number_format($this->getPublicPriceCostWithoutIva(), 2, '.', ''),
                'cost_price_iva' => number_format($this->getPublicPriceCost(), 2, '.', ''),
                'recommended_price' => number_format($this->getRecommendedPrice(), 2, '.', ''),
                'price' => number_format($this->getPrice(), 2, '.', ''),
                'profit_margin' => $this->getProfitMargin(),
                'full_price_margin' => $this->getFullPriceMargin(),
            ];
        } else if ($options['action'] == 'promotion') {
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
        } else {
            return [
                'status' => false,
                'message' => 'No se ha enviado una acción valida'
            ];
        }
    }

    /**
     * Función para obtener el nombre del producto segun la franquicia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getName()
    {
        $productCustom = ProductCustomModel::where('franchise', FranchiseModel::get('id'))->where('product', $this->id);
        if ($productCustom->count() == 0) {
            return $this->name;
        } else {
            $productCustom = $productCustom->first();
            if ($productCustom->name != null) {
                return $productCustom->name;
            } else {
                return $this->name;
            }
        }
    }

    /**
     * Función para obtener la descripción del producto segun la franquicia
     *
     * @since 3.0.0
     * @author David Cortés <david@devuelving.com>
     * @return void
     */
    public function getDescription()
    {
        $productCustom = ProductCustomModel::where('franchise', FranchiseModel::get('id'))->where('product', $this->id);
        if ($productCustom->count() == 0) {
            return $this->description;
        } else {
            $productCustom = $productCustom->first();
            if ($productCustom->description != null) {
                return $productCustom->description;
            } else {
                return $this->description;
            }
        }
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
        $productCustom = ProductCustomModel::where('franchise', FranchiseModel::get('id'))->where('product', $this->id);
        if ($productCustom->count() == 0) {
            if ($type == 'meta_title') {
                return $this->getName();
            } else if ($type == 'meta_description') {
                return $this->getShortDescription(250);
            } else if ($type == 'meta_keywords') {
                return null;
            }
        } else {
            $productCustom = $productCustom->first();
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
    public function getStock()
    {
        if (!$this->unavailable && !$this->discontinued) {
            if ($this->stock_type == 1) {
                $additions = ProductStockModel::where('product_stock.type', '=', 2)->where('product_stock.product', '=', $this->id)->sum('stock');
                $subtractions = ProductStockModel::where('product_stock.type', '=', 1)->where('product_stock.product', '=', $this->id)->sum('stock');
                return $additions - $subtractions;
            } else if ($this->stock_type == 3) {
                $stock = $this->getProductProvider()->stock;
                $date = Carbon::now()->subDays(2)->toDateString();
                $reserved = OrderDetailModel::join('orders', 'order_details.order', '=', 'orders.id')
                ->where('product', $this->id)
                ->whereIn('orders.status', [1,2]);
                $reserved->where(function ($query) use ($date) { 
                    $query->where('orders.payment_method', '!=', 6);
                    $query->orWhereDate('orders.created_at', 'LIKE', '>', $date);
                    $query->orWhereNotNull('orders.payment_date');
                    $query->orWhere('orders.status', 2);
                });
                return $stock-($reserved->sum('order_details.units'));
            } else if ($this->stock_type == 4) {
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
        if ((boolean) FranchiseModel::custom('visible_price', false) || auth()->check()) {
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
    public function visibleDiscounts()
    {
        if (((FranchiseModel::get('type') == 0) && ($this->getPublicMarginProfit() < 25)) || ((!(boolean) FranchiseModel::custom('visible_discounts', true)) || ($this->getPublicMarginProfit() < 5))) {
            return false;
        } else {
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
    public function print()
    {
        $product = ProductModel::find($this->id);
        return view('modules.catalog.product', compact('product'));
    }
}
