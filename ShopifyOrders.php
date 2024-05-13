<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ShopifyOrdersItems;

class ShopifyOrders extends Model {

    use HasFactory;

    protected $table = 'shopify_orders';
    protected $primaryKey = 'id';
    protected $fillable = ['shop', 'order_id', 'name', 'fulfillment_status',
        'financial_status', 'local_status', 'shopify_cancelled_at', 'processed', 
        'shopify_created_at', 'shopify_updated_at', 'created_at', 'updated_at'];

    public function items() {
        return $this->hasMany(ShopifyOrdersItems::class, 'order_id', 'order_id');
    }

    public function saveOrder($post, $header_domain) {
        $model = new ShopifyOrders();

        $fillables = $model->getFillable();
        $data_insertion = [];

        foreach ($fillables as $fillable) {
            if ($fillable == 'order_id') {
                $data_insertion[$fillable] = $post['id'];
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
                if (isset($post[$fillable]) && is_array($post[$fillable])) {
                    $data_insertion[$fillable] = trim(implode(',', $post[$fillable]), ',');
                } elseif (isset($post[$fillable])) {
                    $data_insertion[$fillable] = $post[$fillable];
                } else {
                    $data_insertion[$fillable] = null;
                }
            }
        }
        
        if (isset($post['cancelled_at']) && !empty($post['cancelled_at'])) {
            $data_insertion['shopify_cancelled_at'] = $post['cancelled_at'];
            $data_insertion['local_status'] = 'cancelled';
        }elseif(isset($post['financial_status']) && strtolower($post['financial_status']) == 'paid'){
            $data_insertion['local_status'] = 'paid';
        }
        
        $model->updateOrCreate(
                ['order_id' => $post['id']],
                $data_insertion
        );
    }

}
