<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Colors extends Model
{
    use HasFactory;
    protected $table = 'colors';
    protected $primaryKey = 'id';
    protected $fillable = ['title', 'code', 'quantity'];
    
    public static $rules = [
        'title' => 'required|string|regex:/^[A-Za-z\s]+$/',
        'code' => 'required',
        'quantity' => 'required|numeric|min:0',
    ];

    public static $messages = [
        'title.alpha' => 'The title field should only contain letters.',
        // Add more custom error messages for specific validation rules if needed
    ];

    public function update(array $attributes = [], array $options = [])
    {
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
