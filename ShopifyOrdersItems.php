<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ShopifyOrders;

class ShopifyOrdersItems extends Model {

    use HasFactory;

    protected $table = 'shopify_orders_items';
    protected $primaryKey = 'id';
    protected $fillable = ['shop', 'order_id', 'item_id', 'product_id', 'variant_id', 'quantity', 'processed', 'sku',
        'shopify_created_at', 'shopify_updated_at', 'created_at', 'updated_at'];

    public function order() {
        return $this->hasOne(ShopifyOrders::class, 'order_id', 'order_id');
    }

}
