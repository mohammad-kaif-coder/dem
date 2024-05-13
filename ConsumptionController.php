<?php

namespace App\Http\Controllers\Api\v1;
use App\Models\Consumption;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Fabric;
use App\Models\ProductImage;
use App\Models\ShopifyProducts;
use App\Models\ShopifyProductsVariants;

class ConsumptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $consumptions = Consumption::all();

        return response()->json([
            'success' => true,
            'data' => $consumptions,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), (new Consumption())->validationRules(), (new Consumption())->validationMessages());
    
        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessage = $errors->first();
    
            return response()->json([
                'success' => false,
                'errors' => $errors,
                'message' => $errorMessage
            ], 422);
        }
    
        // Process the data and create/update the Consumption models
        $variantId = $request->input('variant_id');
        $variantColorId = $request->input('variant_color_id');
        $consumptions = $request->input('consumption');
    
        // Fetch the variant based on the variant_id
        $variant = ShopifyProductsVariants::where('variant_id', $variantId)->first();
    
        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => 'Variant not found'
            ], 404);
        }
    
        // Retrieve the associated product using the product_id from the variant
        $product = ShopifyProducts::where('product_id', $variant->product_id)->first();
    
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
    
        $responseData = [
            'id' => $product->id,
            'product_id' => $product->product_id,
            'variant_id' => $variant->variant_id,
            'barcode' => $variant->barcode,
            'sku' => $variant->sku,
            'title' => $product->title,
            'display_name' => $variant->title,
            'price' => $variant->price,
            'compare_at_price' => $variant->compare_at_price,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
            'handle' => $product->handle,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'shopify_created_at' => $product->shopify_created_at,
            'shopify_updated_at' => $product->shopify_updated_at,
            'fabric' => [],
            'image' => [
                'id' => $product->image->id,
                'product_id' => $product->image->product_id,
                'image_id' => $product->image->image_id,
                'src' => $product->image->src,
                'created_at' => $product->image->created_at,
                'updated_at' => $product->image->updated_at,
            ]
        ];
            
        // Calculate the total consumption for each fabric_id
        $updatedConsumptions = [];
        foreach ($consumptions as $consumptionData) {
            $fabricId = $consumptionData['fabric_id'];
            $consumptionValue = abs(round((float) $consumptionData['consumption'], 2)); // Convert to float
        
            // Check if a consumption entry already exists for the given variant, color, and fabric
            $existingConsumption = Consumption::where('variant_id', $variantId)
                ->where('variant_color_id', $variantColorId)
                ->where('fabric_id', $fabricId)
                ->first();
        
            if ($existingConsumption) {
                // Update the existing consumption entry
                $existingConsumption->consumption = $consumptionValue;
                $existingConsumption->save();
            } else {
                // Create a new consumption entry
                $consumption = new Consumption();
                $consumption->variant_id = $variantId;
                $consumption->variant_color_id = $variantColorId;
                $consumption->fabric_id = $fabricId;
                $consumption->consumption = $consumptionValue;
                $consumption->save();
            }
        
            // Fetch the fabric details based on the fabric_id
            $fabric = Fabric::find($fabricId);
        
            if ($fabric) {
                // Add the fabric consumption to the response data
                $updatedConsumptions[] = [
                    'id' => $fabric->id,
                    'name' => $fabric->name,
                    'consumption' => $consumptionValue
                ];
            }
        }
            
        $responseData['fabric'] = $updatedConsumptions;
    
        return response()->json([
            'success' => true,
            'data' => $responseData,
            'message' => 'Consumptions added successfully'
        ]);
    }
                     
                    
    public function show($id)
    {
        $consumption = Consumption::find($id);

        if (!$consumption) {
            return response()->json([
                'success' => false,
                'message' => 'Consumption not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $consumption,
        ]);
    }

    public function update(Request $request, $id)
    {
        $consumption = Consumption::find($id);

        if (!$consumption) {
            return response()->json([
                'success' => false,
                'message' => 'Consumption not found',
            ], 404);
        }

        $data = $request->validate([
            'variant_color_id' => 'required|exists:variant_colors,id',
            'fabric_id' => 'required|exists:fabric,id',
            'variant_id' => 'nullable|exists:shopify_products_variants,id',
            'sku_id' => 'nullable|exists:skus,id',
            'consumption' => 'required|regex:/^\d*(\.\d{1,2})?$/',
            'status' => 'nullable|in:0,1',
        ]);

        $consumption->update($data);

        return response()->json([
            'success' => true,
            'data' => $consumption,
            'message' => 'Consumption updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $consumption = Consumption::find($id);

        if (!$consumption) {
            return response()->json([
                'success' => false,
                'message' => 'Consumption not found',
            ], 404);
        }

        $consumption->delete();

        return response()->json([
            'success' => true,
            'message' => 'Consumption deleted successfully',
        ]);
    }
}
