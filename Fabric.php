<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Fabric extends Model
{
    protected $table = 'fabric';

    protected $fillable = ['name', 'type', 'color', 'color_code', 'status'];

    public static $rules = [
        'name' => 'required|exists:fabric,id',
        'type' => 'required',
        'color' => 'required',
        'color_code' => 'required',
    ];
    
    protected $casts = [
        'status' => 'boolean',
    ];
    
    public static $messages = [
        'name.required' => 'The name field is required.',
        'type.required' => 'The type field is required.',
        'color.required' => 'The color field is required.',
        'color_code.required' => 'The color code field is required.',
    ];
    public function toArray()
    {
        $array = parent::toArray();
        $array['status'] = $this->status;
        return $array;
    }

    public function getStatusAttribute($value)
    {
        return isset($value) ? (int) $value : 0;
    }
    

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }
    public function consumptions()
    {
        return $this->hasMany(Consumption::class, 'fabric_id');
    }
    
    // Define any relationships or additional methods here
}

