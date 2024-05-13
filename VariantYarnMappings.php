<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VariantYarnMappings extends Model {

    use HasFactory;

    protected $table = 'variant_yarn_mappings';
    protected $fillable = ['variant_id', 'yarn_id', 'yarn_color_id'];
    public static $rules = [
        'variant_id' => 'required',
        'yarn_color_id' => 'required'
    ];

    public function yarn_color() {
        return $this->hasOne('App\Models\YarnColors', 'id', 'yarn_color_id')->with(['inventory' => function ($query) {
                        // Select only the stock field from inventory
                        $query->select('yarn_color_id', DB::raw('SUM(stock) as stock'));
                        $query->groupBy('yarn_color_id');
                    }])->with('yarn');
    }

    public function consumption() {
        return $this->hasOne('App\Models\VariantYarnColorConsumption', 'variant_id', 'variant_id');
    }

    public function max_consumption() {
        return $this->hasOneThrough('App\Models\VariantYarnColorConsumption', 'yarn_color_id', 'yarn_color_id')
                        ->selectRaw('yarn_color_id, max(consumption) as aggregate')
                        ->groupBy('yarn_color_id');
    }

    public function variant() {
        return $this->hasOne('App\Models\ShopifyProductsVariants', 'variant_id', 'variant_id')->with('product:id,product_id,handle,title');
    }

}
