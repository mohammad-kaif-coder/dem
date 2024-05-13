<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Consumption extends Model {

    use HasFactory;

    protected $table = 'consumptions';
    protected $fillable = [
        'variant_id',
        'variant_color_id',
        'consumption',
        'fabric_id',
    ];
    public static $rules = [
        'variant_id' => 'required',
        'variant_color_id' => 'required',
        'consumption' => 'required|regex:/^\d*(\.\d{1,2})?$/',
        'fabric_id' => 'required'
    ];

    public function fabric() {
        return $this->belongsTo(Fabric::class);
    }

    public function validationRules() {
        return [
            'variant_id' => 'required',
            'variant_color_id' => 'required',
            'consumption' => 'required|array',
            'consumption.*.fabric_id' => 'required',
            'consumption.*.consumption' => 'required|numeric',
        ];
    }

    public function validationMessages() {
        return [
            'variant_id.required' => 'The variant ID is required.',
            'variant_color_id.required' => 'The variant color ID is required.',
            'consumption.required' => 'The consumption array is required.',
            'consumption.array' => 'The consumption must be an array.',
            'consumption.*.fabric_id.required' => 'The fabric ID in consumption is required.',
            'consumption.*.consumption.required' => 'The consumption value in consumption is required.',
            'consumption.*.consumption.numeric' => 'The consumption value must be numeric.',
        ];
    }

}
