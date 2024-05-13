<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Consumption;
use App\Models\Fabric;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ShopifyProductsVariants;
use App\Models\VariantColor;
use App\Models\VariantColorCombination;

class D_MatchingVariantColorController extends Controller
{

    public function getMatchingVariantColor(Request $request)
    {
        $variantId = $request->variantId;

        // Retrieve the variant from the ShopifyProductsVariants model
        $variant = ShopifyProductsVariants::where('variant_id', $variantId)->first();

        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => 'Variant not found'
            ], 404);
        }

        // Extract the color from the variant's title
        $color = $this->extractColorFromTitle($variant->title);

        // Retrieve the matching variant colors based on the color name
        $matchingVariantColors = VariantColor::where('name', 'like', '%' . $color . '%')->get();

        // Build the response data
        $responseData = [];
        $responseData['id'] = $variant->variant_id;
        $responseData['name'] = $variant->title;

        // Check if matching colors exist in VariantColor table
        if ($matchingVariantColors->isNotEmpty()) {
            $matchingColor = $matchingVariantColors->first();
            $variantColorId = $matchingColor->id;

            $matchingColorData = [
                'id' => $matchingColor->id,
                'name' => $matchingColor->name,
                'type' => $matchingColor->type,
                'fabric' => []
            ];

            // Retrieve fabric information for the selected variant color
            $fabricRecords = VariantColorCombination::where('variant_color_id', $variantColorId)
                ->with('fabric')
                ->get();

            foreach ($fabricRecords as $fabricRecord) {
                if ($fabricRecord->fabric) {
                    $fabricData = [
                        'id' => $fabricRecord->fabric->id,
                        'name' => $fabricRecord->fabric->name,
                        'consumption' => $this->calculateFabricConsumption($fabricRecord->fabric->id, $variantId, $variantColorId),
                        'stock' => $this->calculateTotalStockForFabric($fabricRecord->fabric->id)
                    ];

                    $matchingColorData['fabric'][] = $fabricData;
                }
            }

            $responseData['matching_colors'] = $matchingColorData;
        } else {
            $responseData['matching_colors'] = null;
        }

        return response()->json([
            'success' => true,
            'data' => $responseData
        ]);
    }


    private function calculateFabricConsumption($fabricId, $variantId, $variantColorId)
    {
        // Retrieve the consumption records for the given fabric ID, variant ID, and variant color ID
        $consumptions = Consumption::where('fabric_id', $fabricId)
            ->where('variant_id', $variantId)
            ->where('variant_color_id', $variantColorId)
            ->pluck('consumption');

        // Check if any consumption records exist
        if ($consumptions->isEmpty()) {
            return 0;
        }

        // Calculate the total consumption
        $totalConsumption = $consumptions->sum();

        return $totalConsumption;
    }


    private function calculateTotalStockForFabric($fabricId)
    {
        // Retrieve the fabric
        $fabric = Fabric::find($fabricId);

        // Retrieve the total stock for the fabric
        $totalStock = $fabric->inventory()->sum('stock');

        return $totalStock;
    }


    private function extractColorFromTitle($title)
    {
        // Logic to extract the color from the title
        // You can customize this according to your specific requirements

        // For example, if the title format is "{Size} / {Color}", you can extract the color using the following code:
        $parts = explode(' / ', $title);
        $color = end($parts);

        return $color;
    }

}
