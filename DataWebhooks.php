<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ShopifyProducts;
use App\Models\ShopifyProductsVariants;
use App\Models\ShopifyProductsImages;
use App\Models\ShopifyOrdersItems;

class DataWebhooks extends Model {

    use HasFactory;

    public function handle($__DATA, $app_code, $version) {
//        echo "\n\n*********";
        /**
         * Check webhook topic
         */
//        if (in_array($__DATA['data']['headers']['x-shopify-topic'][0], ['products/update', 'products/create'])) {
        $headers = isset($__DATA['data']['headers']) ? $__DATA['data']['headers'] : [];
        $post = isset($__DATA['data']['payload']) ? $__DATA['data']['payload'] : [];

        $header_domain = (isset($headers['x-shopify-shop-domain'][0]) ? $headers['x-shopify-shop-domain'][0] : '');
        $header_topic = (isset($headers['x-shopify-topic'][0]) ? $headers['x-shopify-topic'][0] : '');
//            echo "product webhook";
//        echo $header_domain;exit;
        /**
         * If webhook contains product create/update
         */
        if (!empty($header_topic) && isset($post['id']) && !empty($post['id'])) {
            switch ($header_topic) {
                case 'products/create':
                case 'products/update':
                    $r = $this->product($headers, $post, $app_code, $header_domain);
                    break;
                case 'products/delete':
                    $r = $this->productDelete($headers, $post, $app_code);
                    break;
                case 'orders/create':
                case 'orders/updated':
                    echo "Order created/updated ===> " . $post['id'];
                    $r = $this->order($headers, $post, $app_code, $header_domain);
                    break;
                case 'orders/cancelled':
                    $r = $this->orderCancelled($headers, $post, $app_code, $header_domain);
                    break;
                default:
                    break;
            }
        }
//        }

        if (isset($update_response['error'])) {
            Log::info(' \n\n _Tiktok_ShopifyWebhooks:: error: ' . $update_response['error']);
            //do nothing
        }
        return true;
    }

    /**
     * Product create/update webhook
     *
     * @return \Illuminate\Http\Response
     */
    public function product($headers, $post, $app_code, $header_domain) {
        $model = new ShopifyProducts();
        $fillables = $model->getFillable();
        $data_insertion = [];
//        print_R($post);
//        print_R($fillables);
//        exit;
//        echo $header_domain;
        foreach ($fillables as $fillable) {
            if ($fillable == 'product_id') {
                $data_insertion[$fillable] = $post['id'];
            } elseif ($fillable == 'description') {
                $data_insertion[$fillable] = $post['body_html'];
            } elseif ($fillable == 'created_at') {
                $data_insertion['shopify_created_at'] = $post['created_at'];
            } elseif ($fillable == 'updated_at') {
                $data_insertion['shopify_updated_at'] = $post['updated_at'];
            } elseif ($fillable == 'shop_id') {
                $data_insertion['shop_id'] = $shop_id;
            } elseif ($fillable == 'app_id') {
                $data_insertion['app_id'] = $app_id;
            } elseif ($fillable == 'shop') {
                $data_insertion['shop'] = $header_domain;
            } elseif (in_array($fillable, ['shopify_created_at', 'shopify_updated_at'])) {
                
            } else {
                $data_insertion[$fillable] = (isset($post[$fillable]) ? $post[$fillable] : '');
            }
        }
        try {
            $u = $model->updateOrCreate(
                    [
                        'shop' => $header_domain,
                        'product_id' => $post['id']
                    ],
                    $data_insertion
            );
        } catch (Exception $ex) {
            return ['error' => true];
        }

        /**
         * Get and delete deleted variants
         */
        $variants = (isset($post['variants']) ? $post['variants'] : []);
        $variants_ids = array_column($variants, 'id');

        $variant_model = new ShopifyProductsVariants();
        $existing_variants = $variant_model->select('variant_id')->where(['product_id' => $post['id']])->get();
        $existing_variants_array = $existing_variants->toArray();
        $existing_variants_ids = array_column($existing_variants_array, 'variant_id');
        $remove_variants = array_diff($existing_variants_ids, $variants_ids);

        if (sizeof($remove_variants)) {
            $r = $variant_model->whereIn('variant_id', $remove_variants)->where('product_id', $post['id'])->delete();
        }

        /**
         * Create or update variants
         */
        $variant_fillables = $variant_model->getFillable();
        foreach ($variants as $variant) {
            $data_insertion = [];
            foreach ($variant_fillables as $fillable) {
                if ($fillable == 'variant_id') {
                    $data_insertion[$fillable] = $variant['id'];
                } elseif ($fillable == 'display_name') {
                    $data_insertion[$fillable] = $post['title'] . ' - ' . $variant['title'];
                } elseif ($fillable == 'inventory_management') {
                    $data_insertion[$fillable] = $this->getInventoryManagementValue($variant['inventory_management']);
                } elseif (in_array($fillable, ['available_for_sale', 'created_at', 'updated_at'])) {
                    
                } elseif (in_array($fillable, ['price', 'compare_at_price'])) {
                    $data_insertion[$fillable] = ((isset($variant[$fillable]) && !empty($variant[$fillable])) ? $variant[$fillable] : null);
                } else {
                    $data_insertion[$fillable] = (isset($variant[$fillable]) ? $variant[$fillable] : '');
                }
            }
            $variant_model->updateOrCreate(
                    ['variant_id' => $variant['id']],
                    $data_insertion
            );
        }

        /**
         * Get and delete deleted images
         */
        $images = (isset($post['images']) ? $post['images'] : []);
        $images_ids = array_column($images, 'id');
        $images_model = new ShopifyProductsImages();
        $existing_images = $images_model->select('image_id')->where(['product_id' => $post['id']])->get();
        $existing_images_array = $existing_images->toArray();
        $existing_images_ids = array_column($existing_images_array, 'image_id');
        $remove_images = array_diff($existing_images_ids, $images_ids);
        if (sizeof($remove_images)) {
            $images_model->whereIn('image_id', $remove_images)->where('product_id', $post['id'])->delete();
        }

        /**
         * Create or update images
         */
        $image_fillables = $images_model->getFillable();
        foreach ($images as $image) {
            $data_insertion = [];
            foreach ($image_fillables as $fillable) {
                if ($fillable == 'image_id') {
                    $data_insertion[$fillable] = $image['id'];
                } elseif (in_array($fillable, ['created_at', 'updated_at'])) {
                    
                } else {
                    $data_insertion[$fillable] = $image[$fillable];
                }
            }

            $images_model->updateOrCreate(
                    ['image_id' => $image['id']],
                    $data_insertion
            );
        }

        return true;
    }

    /**
     * Product delete webhook
     * @param $headers, $post, $app_code
     * @return type
     */
    public function productDelete($headers, $post, $app_code) {
        $product_id = $post['id'];
        $model = new ShopifyProducts();
        $variants_model = new ShopifyProductsVariants();
        $images_model = new ShopifyProductsImages();

        $variants_model->where(['product_id' => $product_id])->delete();
        $images_model->where(['product_id' => $product_id])->delete();
        $model->where(['product_id' => $product_id])->delete();

        return true;
    }

    public function order($headers, $post, $app_code, $header_domain) {
        $model = new ShopifyOrders();
        $model->saveOrder($post, $header_domain);

        $model = new ShopifyOrdersItems();
        $fillables = $model->getFillable();

        if (isset($post['line_items'])) {
            foreach ($post['line_items'] as $line_item) {
                $data_insertion = [];
                foreach ($fillables as $fillable) {
                    if ($fillable == 'order_id') {
                        $data_insertion[$fillable] = $post['id'];
                    } elseif ($fillable == 'item_id') {
                        $data_insertion['item_id'] = $line_item['id'];
                    } elseif ($fillable == 'created_at') {
                        $data_insertion['shopify_created_at'] = $post['created_at'];
                    } elseif ($fillable == 'updated_at') {
                        $data_insertion['shopify_updated_at'] = $post['updated_at'];
                    } elseif ($fillable == 'shop') {
                        $data_insertion['shop'] = $header_domain;
                    } elseif ($fillable == 'processed') {
                        $data_insertion['processed'] = 0;
                    } elseif (in_array($fillable, ['shopify_created_at', 'shopify_updated_at'])) {
                        
                    } else {
                        if (isset($line_item[$fillable]) && is_array($line_item[$fillable])) {
                            $data_insertion[$fillable] = trim(implode(',', $line_item[$fillable]), ',');
                        } elseif (isset($line_item[$fillable])) {
                            $data_insertion[$fillable] = $line_item[$fillable];
                        } else {
                            $data_insertion[$fillable] = null;
                        }
                    }
                }


                $model->updateOrCreate(
                        [
                            'item_id' => $line_item['id']
                        ],
                        $data_insertion
                );
            }
        }

        return true;
    }

    public function orderCancelled($headers, $post, $app_code, $header_domain) {
        $model = new ShopifyOrders();
        $model->saveOrder($post, $header_domain);
    }

}
