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
use devuelving\core\ProductStockModel;
use devuelving\core\ProductCustomModel;
use Illuminate\Database\Eloquent\Model;
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
        'slug', 'name', 'description', 'stock_type', 'minimum_stock', 'transport', 'weight', 'volume', 'tax', 'brand', 'tags', 'parent', 'franchise', 'promo', 'double_unit', 'liquidation', 'unavailable', 'discontinued', 'highlight', 'shipping_canarias', 'price_rules', 'meta_title', 'meta_description', 'meta_keywords',
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
     * Función para obtener los datos de un producto
     *
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
     * @return void
     */
    public function getDefaultImage()
    {
        return $this->getImages()[0];
    }

    /**
     * Función para obtener los ean del producto
     *
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
     * @return void
     */
    public function getBrand()
    {
        return BrandModel::find($this->brand);
    }

    /**
     * Función para obtener el valor del iva de este producto
     *
     * @return void
     */
    public function getTax()
    {
        $tax = TaxModel::find($this->tax);
        return $tax->value / 100;
    }

    /**
     * Función para obtene el producto proveedor
     *
     * @param boolean $cheapest
     * @return void
     */
    public function getProductProvider($cheapest = false)
    {
        try {
            if ($this->franchise === null) {
                if (! $cheapest) {
                    if ($this->price_rules == 1) {
                        $rule = 'asc';
                    } else {
                        $rule = 'desc';
                    }
                } else {
                    $rule = 'asc';
                }
                $productProvider = ProductProviderModel::join('provider', 'product_provider.provider', '=', 'provider.id')
                ->where('product_provider.product', $this->id)
                ->where('provider.active', 1)
                ->orderBy('product_provider.cost_price', $rule)
                ->select('product_provider.*', 'provider.name')
                ->first();
            } else {
                if (! $cheapest) {
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
     * Función para obtener el precio con el margen de beneficio del proveedor
     *
     * @param boolean $tax
     * @return void
     */
    public function getPublicPriceCost($tax = true)
    {
        if ($this->franchise === null) {
            if ($this->getProductProviderData('cost_price') != null) {
                $price = $this->getProductProviderData('cost_price');
                $provider = ProviderModel::find($this->getProductProviderData('provider'));
                $total = $price + ($price * ($provider->profit_margin / 100));
                if ($tax) {
                    $total = $total + ($total * $this->getTax());
                }
                return $total;
            }
        } else {
            $price = $this->getProductProviderData('cost_price');
            if ($tax) {
                $total = $price * $this->getTax();
            }
            return $total;
        }
        return null;
    }

    /**
     * Función para obtener el precio de coste sin IVA
     *
     * @return void
     */
    public function getPublicPriceCostWithoutIva()
    {
        return $this->getPublicPriceCost(false);
    }

    /**
     * Función para obtener el precio sin IVA
     *
     * @return void
     */
    public function getPriceWithoutIva()
    {
        if ($this->getPrice() != null) {
            return $this->getPrice() / ($this->getTax() + 1);
        }
        return null;
    }

    /**
     * Función para obtener el precio con el margen de beneficio del proveedor anterior al cambio
     *
     * @return void
     */
    public function getOldPublicPriceCost()
    {
        if ($this->getProductProviderData('cost_price') != null) {
            $productPriceUpdate = DB::table('product_price_update')->where('product', $this->id)->orderBy('id', 'desc')->first();
            $price = $productPriceUpdate->price;
            $provider = ProviderModel::find($this->getProductProviderData('provider'));
            $total = $price + ($price * ($provider->profit_margin / 100));
            $total = $total + ($total * $this->getTax());
            return $total;
        }
        return null;
    }

    /**
     * Función para obtener la fecha de la ultima actualización de los precios de un producto
     *
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
     * @return void
     */
    public function getRecommendedPrice()
    {
        if ($this->getProductProviderData('recommended_price') != null) {
            $price = $this->getProductProviderData('recommended_price');
            return $price;
        }
        return null;
    }

    /**
     * Función para comprobar si tiene un precio customizado
     *
     * @return void
     */
    public function checkCustomPrice()
    {
        $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', FranchiseModel::get('code'))->whereNotNull('price')->get();
        if (count($productCustom) == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Función para comprobar el tipo de precio custom del precio
     *
     * @return void
     */
    public function typeCustomPrice()
    {
        $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', FranchiseModel::get('code'))->whereNotNull('price');
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
     * @return void
     */
    public function checkPromotion()
    {
        $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', FranchiseModel::get('code'))->whereNotNull('promotion')->get();
        if (count($productCustom) == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Función para obtener el precio de venta al publico
     *
     * @return void
     */
    public function getPrice()
    {
        if ($this->getProductProviderData('default_price') != null) {
            if ($this->checkCustomPrice()) {
                $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', FranchiseModel::get('code'))->first();
                if ($productCustom->price_type == 1) {
                    $price = $productCustom->price;
                } else {
                    $price = $this->getPublicPriceCost() + ($this->getPublicPriceCost() * ($productCustom->price / 100));
                }
            } else {
                $price = $this->getProductProviderData('default_price');
            }
            return $price;
        }
        return null;
    }

    /**
     * Función para obtener las categorias del producto
     *
     * @return void
     */
    public function getCategories()
    {
        return ProductCategoryModel::where('product', $this->id)->get();
    }

    /**
     * FUnción para obtener el beneficio de un producto
     *
     * @return void
     */
    public function getProfit()
    {
        if ($this->getPrice() != null) {
            $publicPrice = $this->getPrice();
            $costPrice = $this->getPublicPriceCost();
            return ($publicPrice - $costPrice) / $costPrice;
        }
        return null;
    }

    /**
     * Función para obtener el margen beneficio real del producto
     *
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
        return null;
    }

    /**
     * Función para obtener el descuento entre el precio de venta y el PVPR
     *
     * @return void
     */
    public function getPublicMarginProfit()
    {
        $publicPrice = $this->getPrice();
        $recommendedPrice = $this->getRecommendedPrice();
        return round((($recommendedPrice - $publicPrice) / $publicPrice) * 100);
    }

    /**
     * Función para obtener el beneficio entre el precio de coste y el pvpr
     *
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
     * @param array $options
     * @return void
     */
    public function productCustom($options = [])
    {
        $productCustom = ProductCustomModel::get(FranchiseModel::get('code'), $this->id);
        if ($options['action'] == 'price') {
            if ($options['price'] == null || $options['price_type'] == null) {
                $productCustom->price = null;
                $productCustom->price_type = null;
            } else {
                $publicPrice = $options['price'];
                $costPrice = $this->getPublicPriceCost();
                $margin = round((($publicPrice - $costPrice) / $costPrice) * 100);
                if ($margin < 1) {
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
            ProductCustomModel::checkClear($productCustom->id);
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
            ProductCustomModel::checkClear($productCustom->id);
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
     * @return void
     */
    public function getName()
    {
        $productCustom = ProductCustomModel::get(FranchiseModel::get('code'), $this->id);
        if ($productCustom->name == null) {
            return $this->name;
        } else {
            return $productCustom->name;
        }
    }

    /**
     * Función para obtener la descripción del producto segun la franquicia
     *
     * @return void
     */
    public function getDescription()
    {
        $productCustom = ProductCustomModel::get(FranchiseModel::get('code'), $this->id);
        if ($productCustom->description == null) {
            return $this->description;
        } else {
            return $productCustom->description;
        }
    }

    /**
     * Función para añadir un registro de actualizaciones de precios
     *
     * @return void
     */
    public function addUpdatePrice($costPrice)
    {
        try {
            $productPriceUpdate = DB::table('product_price_update')->where('product', $this->id)->orderBy('id', 'desc')->first();
            $oldPrice = $productPriceUpdate->price;
        } catch (\Exception $e) {
            // report($e);
            $oldPrice = 0;
        }
        if ($costPrice != $oldPrice) {
            DB::table('product_price_update')
            ->insert([
                'product' => $this->id,
                'price' => $costPrice,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString()
            ]);
        }
    }

    /**
     * Devuelve stock actual, true si no mantenemos stock o false si está agotado.
     *
     * @return boolean
     */
    public function getStock()
    {
        if (! $this->unavailable) {
            if ($this->stock_type == 1) {
                $additions = ProductStockModel::where('product_stock.type', '=', 2)
                ->where('product_stock.product', '=', $this->id)
                ->sum('stock');
                $subtractions = ProductStockModel::where('product_stock.type', '=', 1)
                ->where('product_stock.product', '=', $this->id)
                ->sum('stock');
                return $additions-$subtractions;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Función para imprimir un banner del producto
     *
     * @return void
     */
    public function print()
    {
        $product = ProductModel::find($this->id);
        return view('modules.catalog.product', compact('product'));
    }
}
