<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Yarns extends Model {

    use HasFactory;

    protected $table = 'yarns';
    protected $primaryKey = 'id';
    protected $fillable = ['shop', 'name', 'status'];
    public static $rules = [
        'shop' => 'required',
        'name' => 'required',
        'status' => 'required'
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

}
