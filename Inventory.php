<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model {

    use HasFactory;

    protected $fillable = ['fabric_id', 'yarn_color_id', 'stock', 'notes',
        'order_id', 'item_id', 'order_status', 'status'];
    protected $rules = [
        'yarn_color_id' => 'required',
        'stock' => 'required|regex:/^\d*(\.\d{1,2})?$/',
    ];

    public function validate(array $data) {
        return validator($data, $this->rules);
    }

    /**
     * Get the fabric associated with the inventory.
     */
    public function fabric() {
        return $this->belongsTo(Fabric::class, 'fabric_id');
    }

}
