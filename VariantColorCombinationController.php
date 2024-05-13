<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\VariantColorCombination;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;

class VariantColorCombinationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $variantColorCombination = VariantColorCombination::all();
        return response()->json([
            'success' => true,
            'data' => $variantColorCombination,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'variant_color_id' => 'required|exists:variant_colors,id',
            'fabric_id' => 'required|exists:fabric,id',
            'status' => 'nullable|boolean',
        ], VariantColorCombination::messages());
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation failed',
            ], 422);
        }
    
        $variantColorCombination = new VariantColorCombination([
            'variant_color_id' => $request->variant_color_id,
            'fabric_id' => $request->fabric_id,
            'status' => $request->status ?? 1,
        ]);
        $variantColorCombination->save();
    
        return response()->json([
            'success' => true,
            'data' => $variantColorCombination,
            'message' => 'Variant color combination created successfully',
        ], 201);
    }
            
    public function show($id)
    {
        $variantColorCombination = VariantColorCombination::find($id);
        if (!$variantColorCombination) {
            return response()->json([
                'success' => false,
                'message' => 'Variant color combination not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $variantColorCombination,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'variant_color_id' => 'exists:variant_colors,id',
            'fabric_id' => 'exists:fabric,id',
            'status' => 'nullable|boolean',
        ]);

        $variantColorCombination = VariantColorCombination::find($id);
        if (!$variantColorCombination) {
            return response()->json([
                'success' => false,
                'message' => 'Variant color combination not found',
            ], 404);
        }

        if ($request->has('variant_color_id')) {
            $variantColorCombination->variant_color_id = $request->variant_color_id;
        }
        if ($request->has('fabric_id')) {
            $variantColorCombination->fabric_id = $request->fabric_id;
        }
        if ($request->has('status')) {
            $variantColorCombination->status = $request->status;
        }
        $variantColorCombination->save();

        return response()->json([
            'success' => true,
            'data' => $variantColorCombination,
            'message' => 'Variant color combination updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $variantColorCombination = VariantColorCombination::find($id);
        if (!$variantColorCombination) {
            return response()->json([
                'success' => false,
                'message' => 'Variant color combination not found',
            ], 404);
        }

        $variantColorCombination->delete();

        return response()->json([
            'success' => true,
            'message' => 'Variant color combination deleted successfully',
        ]);
    }
}
