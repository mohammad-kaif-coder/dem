<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantColorCombination extends Model
{
    use HasFactory;
    protected $table = 'variant_color_combination';

    protected $fillable = ['variant_color_id', 'fabric_id', 'status'];

    public static function messages()
    {
        return [
            'variant_color_id.required' => 'The variant color ID field is required.',
            'variant_color_id.exists' => 'The selected variant color ID is invalid.',
            'fabric_id.required' => 'The fabric ID field is required.',
            'fabric_id.exists' => 'The selected fabric ID is invalid.',
            'status.boolean' => 'The status field must be a boolean.',
        ];
    }

    public function variantColor()
    {
        return $this->belongsTo(VariantColor::class);
    }

    public function fabric()
    {
        return $this->belongsTo(Fabric::class);
    }

    

}
