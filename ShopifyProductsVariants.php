<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyProductsVariants extends Model {

    use HasFactory;

    protected $table = 'shopify_products_variants';
    protected $primaryKey = 'variant_id';
    protected $fillable = ['product_id', 'variant_id', 'barcode', 'sku',
        'title', 'display_name', 'price', 'compare_at_price', 'inventory_quantity',
        'inventory_item_id', 'created_at', 'updated_at'];

    public function variantColors() {
        return $this->hasMany(VariantColor::class, 'variant_id', 'variant_id');
    }

    public function images() {
        return $this->hasMany(ShopifyProductsImages::class, 'product_id', 'product_id');
    }

    public function consumptions() {
        return $this->hasMany(Consumption::class, 'variant_id', 'variant_id');
    }

    public function product() {
        return $this->hasOne(ShopifyProducts::class, 'product_id', 'product_id');
    }

}
