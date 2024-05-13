<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use App\Models\Yarns;
use App\Models\Inventory;

class YarnColors extends Model {

    use HasFactory;

    protected $table = 'yarn_colors';
    protected $primaryKey = 'id';
    protected $fillable = ['shop', 'yarn_id', 'parent_id', 'name', 'color_code',
        'type', 'minimum_stock', 'status', 'created_at', 'updated_at'];
    public static $rules = [
        'shop' => 'required',
        'yarn_id' => 'required',
        'name' => 'required|string|regex:/^[A-Za-z\s]+$/',
        'color_code' => 'required',
        'type' => 'required',
        'minimum_stock' => 'regex:/^\d*(\.\d{1,2})?$/'
    ];
    public static $messages = [
        'name.alpha' => 'The title field should only contain letters.',
            // Add more custom error messages for specific validation rules if needed
    ];

    public function update(array $attributes = [], array $options = []) {
        $validator = Validator::make($attributes, self::$rules, self::$messages);

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->has('title') && is_numeric($attributes['title'])) {
                throw new \Exception('The title field should only contain letters.');
            }

            throw new \Exception($errors->first());
        }

        return parent::update($attributes, $options);
    }

    public function yarn() {
        return $this->hasOne(Yarns::class, 'id', 'yarn_id');
    }

    public function inventory() {
        return $this->hasMany(Inventory::class, 'yarn_color_id', 'id');
    }

//    public function consumption() {
//        return $this->hasOne(Inventory::class, 'yarn_color_id', 'id');
//    }
}
