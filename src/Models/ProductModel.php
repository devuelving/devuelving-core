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
        'slug', 'name', 'description', 'stock_type', 'minimum_stock', 'transport', 'weight', 'volume', 'tax', 'brand', 'tags', 'variations', 'franchise', 'promotion', 'free_shipping', 'double_unit', 'discount_50', 'discount_progressive', 'units_limit', 'liquidation', 'unavailable', 'discontinued', 'highlight', 'price_edit', 'shipping_canarias', 'cost_price', 'recommended_price', 'default_price', 'price_rules', 'meta_title', 'meta_description', 'meta_keywords',
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
        $cost_price = $productProvider->cost_price + ($productProvider->cost_price * ($provider->profit_margin / 100));
        // Actualizamos los precios de los productos
        DB::table($this->table)->where('id', $this->id)->update([
            'cost_price' => $cost_price,
            'default_price' => $cost_price * ((rand(10, 25) / 100) + 1),
        ]);
    }

    /**
     * Añade una actualización del precio del producto
     *
     * @return void
     */
    public function addUpdatePrice()
    {
        // Obtenemos el anterior precio del producto
        try {
            $productPriceUpdate = DB::table('product_price_update')->where('product', $this->id)->orderBy('id', 'desc')->first();
            $oldPrice = $productPriceUpdate->price;
        } catch (\Exception $e) {
            // report($e);
            $oldPrice = 0;
        }
        // Comprobación de que el precio no es el mismo
        if ($this->cost_price != $oldPrice) {
            // Se añade un nuevo registro con el nuevo precio del producto
            DB::table('product_price_update')->insert([
                'product' => $this->id,
                'price' => $this->cost_price,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString()
            ]);
        }
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
        if ($tax) {
           return $this->cost_price * ($this->getTax() + 1);
        } else {
            return $this->cost_price;
        }
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
        $productPriceUpdate = DB::table('product_price_update')->where('product', $this->id)->orderBy('id', 'desc')->first();
        return $productPriceUpdate->price + ($productPriceUpdate->price * $this->getTax());
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
        return $this->recommended_price;
    }

    /**
     * Función para comprobar si tiene un precio customizado
     *
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
     * @return void
     */
    public function checkPromotion()
    {
        $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', FranchiseModel::get('id'))->whereNotNull('promotion')->first();
        if (count($productCustom) == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Comprueba si el producto esta en promociones por defecto
     *
     * @return void
     */
    public function checkSuperPromo()
    {
        if ($this->promotion == 1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Comprueba que el producto esta en liquidación
     *
     * @return void
     */
    public function checkLiquidation()
    {
        if ($this->liquidation == 1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Comprobamos si los productos tiene la oferta 2x1
     *
     * @return void
     */
    public function checkDoubleUnit()
    {
        if ($this->double_unit == 1) {
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
        $price = 0;
        if ($this->checkCustomPrice()) {
            $productCustom = ProductCustomModel::where('product', $this->id)->where('franchise', FranchiseModel::get('id'))->first();
            if ($productCustom->price_type == 1) {
                $price = $productCustom->price;
            } else {
                $price = $this->default_price + ($this->default_price * ($productCustom->price / 100));
            }
        } else {
            $price = $this->default_price;
        }
        return $price * ($this->getTax() + 1);
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
     * Función para obtener el beneficio de un producto
     *
     * @return void
     */
    public function getProfit()
    {
        if ($this->getPrice() != null) {
            return $this->getPrice() - $this->getPublicPriceCost();
        }
        return 0;
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
        return 0;
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
            $productCustom->checkClear();
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
            $productCustom->checkClear();
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
     * @return boolean
     */
    public function getStock()
    {
        if (!$this->unavailable) {
            if ($this->stock_type == 1) {
                $additions = ProductStockModel::where('product_stock.type', '=', 2);
                $additions->where('product_stock.product', '=', $this->id);
                $additions->sum('stock');
                $subtractions = ProductStockModel::where('product_stock.type', '=', 1);
                $subtractions->where('product_stock.product', '=', $this->id);
                $subtractions->sum('stock');
                return $additions - $subtractions;
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
