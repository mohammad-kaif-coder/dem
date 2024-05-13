<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\ProductYarns;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductYarnsController extends BaseController {

    public function store(Request $request) {
        $validator = Validator::make($request->all(), ProductYarns::$rules, ProductYarns::$messages);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $sucessMessages = [];

            if ($errors->has('product_id')) {
                $sucessMessages = $errors->first('product_id');
            } elseif ($errors->has('yarn_id')) {
                $sucessMessages = $errors->first('yarn_id');
            }

            return response()->json(['success' => false, 'errors' => $errors, 'message' => $sucessMessages], 422);
        }
        
        // Validation passed, process the data and create the Yarns model instance
        $product_yarn = ProductYarns::updateOrCreate(['product_id' => $request->post('product_id')], $request->post());
        $product_yarn = ProductYarns::where('id', $product_yarn->id)->with('yarn')->with('product')->first();

        return response()->json([
                    'success' => true,
                    'message' => 'Yarn saved with product successfully',
                    'data' => $product_yarn
                        ], 201);
    }

    public function destroy($id) {
        try {
            $product_yarn = ProductYarns::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Product yarn not found'], 404);
        }

        $product_yarn->delete();

        return response()->json(['success' => true, 'message' => 'Product yarn deleted successfully']);
    }

    public function getMappedYarns() {
        $shop_products = \App\Models\ShopifyProducts::with("yarn")->get();
        return response()->json($shop_products);
    }

    public function getMappedInventory() { //:variant_id,product_id,:title,display_name
        $shop_products = \App\Models\VariantYarnMappings::with('variant:id,variant_id,product_id,title,display_name')->with("yarn_color")->with('consumption')->get();
//        $shop_products = \App\Models\ShopifyProductsVariants::with("yarn_color")->with('consumption')->get();
        return response()->json($shop_products);
    }
}
