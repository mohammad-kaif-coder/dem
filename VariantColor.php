<?php

namespace App\Models;
use App\Models\ShopifyProductsVariants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantColor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'consumption_json', 'status'];
   

    protected static function boot()
     {
        parent::boot();

        // Set default value for 'status' field
        static::creating(function ($variantColor) {
            $variantColor->status = 1; // Set default status to 1
         });
     }

    public function messages()
    {
        return [
            'name.required' => 'The name field is required.',
            'type.required' => 'The single or multiple field is required.',
            'type.in' => 'The selected single or multiple field is invalid.',
            'consumption_json.json' => 'The consumption_json field must be a valid JSON string.',
        ];
    }
    public function variantColorCombinations()
    {
        return $this->hasMany(VariantColorCombination::class, 'variant_color_id');
    }

    public function fabric()
    {
        return $this->belongsToMany(Fabric::class, 'variant_color_combination', 'variant_color_id', 'fabric_id');
    }

    
  
    public function variant()
    {
        return $this->belongsTo(ShopifyProductsVariants::class, 'variant_id', 'variant_id');
    }
    
        
    
    

    
    // Add any additional methods or relationships here
}
