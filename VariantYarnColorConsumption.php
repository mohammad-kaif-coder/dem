<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantYarnColorConsumption extends Model {

    use HasFactory;

    protected $table = 'variant_yarn_color_consumption';
    protected $fillable = ['variant_id', 'yarn_id', 'yarn_color_id', 'consumption', 'unit'];
    public static $rules = [
        'variant_id' => 'required',
        'yarn_id' => 'required',
        'consumption' => 'required'
    ];

}
