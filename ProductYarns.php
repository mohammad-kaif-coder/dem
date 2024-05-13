<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use App\Models\Yarns;
use App\Models\ShopifyProducts;

class ProductYarns extends Model {

    use HasFactory;

    protected $table = 'product_yarns';
    protected $primaryKey = 'id';
    protected $fillable = ['product_id', 'yarn_id', 'created_at', 'updated_at'];
    public static $rules = [
        'product_id' => 'required|integer',
        'yarn_id' => 'required|integer'
    ];
    public static $messages = [
            // Add more custom error messages for specific validation rules if needed
    ];

    public function update(array $attributes = [], array $options = []) {
        $validator = Validator::make($attributes, self::$rules, self::$messages);

        if ($validator->fails()) {
            $errors = $validator->errors();
            throw new \Exception($errors->first());
        }

        return parent::update($attributes, $options);
    }

    public function yarn() {
        return $this->hasOne(Yarns::class, 'id', 'yarn_id');
    }

    public function product() {
        return $this->hasOne(ShopifyProducts::class, 'product_id', 'product_id');
    }

}
