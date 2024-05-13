<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyProductsImages extends Model {

    use HasFactory;

    protected $table = 'shopify_products_images';
    protected $fillable = ['product_id', 'image_id', 'src', 'created_at', 'updated_at'];

}
