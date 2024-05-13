<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\YarnColors;
use App\Models\VariantYarnMappings;
use App\Models\ShopifyProductsVariants;

class ShopifyProducts extends Model {

    use HasFactory;

    protected $table = 'shopify_products';
    protected $primaryKey = 'id';
    protected $fillable = ['shop', 'product_id', 'handle', 'title',
        'vendor', 'product_type', 'shopify_created_at', 'shopify_updated_at',
        'created_at', 'updated_at'];

    public function variants() {
        return $this->hasMany('App\Models\ShopifyProductsVariants', 'product_id', 'product_id');
    }

    public function image() {
        return $this->hasOne('App\Models\ShopifyProductsImages', 'product_id', 'product_id');
    }

    public function yarn() {
        return $this->hasOne('App\Models\ProductYarns', 'product_id', 'product_id')->with('yarn');
    }

    public function updateOutOfStockStatus($shop_url, $yarn_color_id, $stock_status) {
        $session = \DB::table('sessions')
                ->where('shop', $shop_url)
                ->first();

        if (!isset($session->access_token) || empty($session->access_token)) {
            YarnColors::where('id', $yarn_color_id)->update(['stock_remark' => 'Login session expired']);
            return false;
        }

//        $access_token = 'shpua_25eff3e7e3c19be9846e995a5cb79391';
        $access_token = $session->access_token;
        $to_be_updated_variants = VariantYarnMappings::select('variant_id')->where('yarn_color_id', $yarn_color_id)->get();

        foreach ($to_be_updated_variants as $variant) {
            if (isset($variant->variant_id)) {
//                if ($yarn_color_id == '86') {
                    $shopify_product_variant = ShopifyProductsVariants::select('inventory_item_id')->where(['variant_id' => $variant->variant_id])->first();
                    if (isset($shopify_product_variant->inventory_item_id) && !empty($shopify_product_variant->inventory_item_id)) {
                        ShopifyProducts::getLocationId($shopify_product_variant->inventory_item_id, $shop_url, $access_token, $stock_status, $variant->variant_id );
                    }
//                } else {
//
//
//                    $mutation = 'mutation {
//                        productVariantUpdate(input:  {
//                            id: "gid://shopify/ProductVariant/' . $variant->variant_id . '",
//                            inventoryItem: {
//                                tracked: ' . $stock_status . '
//                            },
//                        }) 
//                        {
//                            userErrors {
//                                field
//                                message
//                            }
//                            productVariant {
//                                id
//                                title
//                                sku
//                                inventoryManagement
//                            }
//                        }
//                    }';
//                    $shopify_calls_model = new ShopifyCalls();
//                    $result = $shopify_calls_model->graph([
//                        'access_token' => $access_token,
//                        'shop' => $shop_url,
//                        "query" => $mutation
//                    ]);
//                }

//                print_R($result);
            }
        }

        YarnColors::where('id', $yarn_color_id)->update([
            'is_in_stock' => (($stock_status == "true") ? 1 : 0),
            'stock_remark' => 'Inventory track updated at ' . date('Y-m-d H:i:s')
        ]);
    }

    public function getLocationId($inventory_item_id, $shop_url, $access_token, $stock_status, $variant_id) {
        //$stock_status = true means product oos
        
        $request_headers = array(
            "Content-type: application/json; charset=utf-8",
            'Expect:',
            'X-Shopify-Access-Token: ' . $access_token,
            'Accept: ' . 'application/json'
        );

        
        $mutation = 'mutation {
                        productVariantUpdate(input:  {
                            id: "gid://shopify/ProductVariant/' . $variant_id . '",
                            inventoryItem: {
                                tracked: true
                            },
                        }) 
                        {
                            userErrors {
                                field
                                message
                            }
                            productVariant {
                                id
                                title
                                sku
                                inventoryManagement
                            }
                        }
                    }';
        $shopify_calls_model = new ShopifyCalls();
        $result = $shopify_calls_model->graph([
            'access_token' => $access_token,
            'shop' => $shop_url,
            "query" => $mutation
        ]);
        
        $url = "https://" . $shop_url . "/admin/api/2023-04/inventory_levels.json?inventory_item_ids=" . $inventory_item_id;
        $response = $shopify_calls_model->curlCall($url, $request_headers, "GET");

        if (isset($response['body']) && !empty($response['body'])) {
            $body = json_decode($response['body'], true);
            if (isset($body['inventory_levels'][0]['location_id']) && !empty($body['inventory_levels'][0]['location_id'])) {
                if ($stock_status == "true") {
                    $available_adjustment = "-" . $body['inventory_levels'][0]['available'];
                } else {
                    if ($body['inventory_levels'][0]['available'] > 100) {
                        $diff = $body['inventory_levels'][0]['available'] - 100;
                        if ($diff > 100) {
                            $available_adjustment = $diff - 100;
                        } else {
                            $available_adjustment = 100 - $diff;
                        }
                    } else if ($body['inventory_levels'][0]['available'] < 100) {
                        $diff = 100 - $body['inventory_levels'][0]['available'];
                        $available_adjustment = 100 + $diff;
                    } else {
                        $available_adjustment = 0;
                    }
                }

                //echo "\n available adjustment: " . $available_adjustment;
                if ($available_adjustment != 0) {
                    $url = "https://" . $shop_url . "/admin/api/2023-04/inventory_levels/adjust.json";
                    $params = array(
                        "location_id" => $body['inventory_levels'][0]['location_id'],
                        "inventory_item_id" => $inventory_item_id,
                        "available_adjustment" => $available_adjustment,
                    );

                    $payload = json_encode($params);
                    $shopify_calls_model = new ShopifyCalls();
                    $response = $shopify_calls_model->curlCall($url, $request_headers, "POST", $payload);
                }
            }
        }

    }

}
