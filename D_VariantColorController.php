<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\VariantColor;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class D_VariantColorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $currentPage = $request->input('page', 1);
        $search = $request->input('search', '');
        $status = $request->input('status');
    
        // Query the VariantColor model with search condition
        $query = VariantColor::query();
        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('type', 'like', '%' . $search . '%');
        }
    
        // Filter the models based on the status value
        if ($status === '1') {
            $query->where('status', 1);
        } elseif ($status === '0') {
            $query->where('status', 0);
        }
    
        // Sort the variant colors by the latest record (based on the 'created_at' column)
        $query->orderBy('created_at', 'desc');
    
        // Paginate the query results
        $variantColors = $query->paginate($perPage, ['*'], 'page', $currentPage);
    
        // Load the fabric relationship for all variant colors
        $variantColors->load('fabric:id,name');
    
        $variantColors->transform(function ($variantColor) {
            $fabric = $variantColor->fabric->map(function ($fabric) {
                return [
                    'id' => $fabric->id,
                    'name' => $fabric->name,
                ];
            });
    
            $selectedFabricIds = $variantColor->fabric->pluck('id')->toArray();
    
            return $variantColor->only(['id', 'name', 'type', 'consumption_json', 'status', 'created_at', 'updated_at']) + [
                'fabric' => $fabric,
                'selected_fabric_ids' => $selectedFabricIds,
            ];
        });
    
        $pagination = [
            'current_page' => $variantColors->currentPage(),
            'last_page' => $variantColors->lastPage(),
            'per_page' => $variantColors->perPage(),
            'total' => $variantColors->total(),
        ];
    
        $firstPageUrl = $variantColors->url(1);
        $lastPageUrl = $variantColors->url($variantColors->lastPage());
    
        return response()->json([
            'success' => true,
            'data' => $variantColors,
            'first_page_url' => $firstPageUrl,
            'last_page_url' => $lastPageUrl,
            'current_page' => $variantColors->currentPage(),
            'last_page' => $variantColors->lastPage(),
            'per_page' => $variantColors->perPage(),
            'total' => $variantColors->total(),
        ]);
    }
                                
        

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

     public function store(Request $request)
     {
         $validator = Validator::make($request->all(), [
             'name' => 'required|unique:variant_colors',
             'type' => 'required|in:single,multiple',
             'consumption_json' => 'nullable|json',
             'fabric_id' => 'required|array',
             'fabric_id.*.id' => 'required|exists:fabric,id',
             'fabric_id.*.name' => 'required',
         ]);
     
         if ($validator->fails()) {

             return response()->json([
                 'success' => false,
                 'errors' => $validator->errors(),
                 'message' => 'Validation failed',
             ], 422);
         }
     
         $variantColor = new VariantColor([
             'name' => $request->name,
             'type' => $request->type,
             'consumption_json' => $request->consumption_json,
             'status' => 1, // Set the default status to 1
         ]);
         $variantColor->save();
     
         $fabricIds = [];
         $selectedFabricIds = [];
         foreach ($request->fabric_id as $fabric) {
             $fabricIds[] = $fabric['id'];
             $selectedFabricIds[] = $fabric['id'];
         }
         $variantColor->fabric()->attach($fabricIds);
     
         $variantColor->load('fabric:id,name');
     
         $responseData = [
            'id'=>  $variantColor->id,
             'name' => $variantColor->name,
             'type' => $variantColor->type,
             'consumption_json' => $variantColor->consumption_json,
             'status' => $variantColor->status, // Include the status field
             'created_at' => $variantColor->created_at, // Include created_at field
             'updated_at' => $variantColor->updated_at, // Include updated_at field

             'fabric' => $variantColor->fabric->map(function ($fabric) {
                 return [
                     'id' => $fabric->id,
                     'name' => $fabric->name,
                 ];
             }),
             'selected_fabric_ids' => $selectedFabricIds,
         ];
     
         return response()->json([
             'success' => true,
             'data' => $responseData,
             'message' => 'Variant color created successfully',
         ], 201);
     }
                                                          
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\VariantColor  $variantColor
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $variantColor = VariantColor::find($id);
    
        if (!$variantColor) {
            return response()->json([
                'success' => false,
                'message' => 'Variant color not found',
            ], 404);
        }
    
        $variantColor->load('fabric:id,name');
    
        return response()->json([
            'success' => true,
            'message' => 'Variant color found',
            'data' => [
                'name' => $variantColor->name,
                'type' => $variantColor->type,
                'consumption_json' => $variantColor->consumption_json,
                'status' => $variantColor->status,
                'fabric' => $variantColor->fabric->map(function ($fabric) {
                    return [
                        'id' => $fabric->id,
                        'name' => $fabric->name,
                    ];
                }),
                'selected_fabric_ids' => $variantColor->fabric->pluck('id'),
            ],
        ]);
    }
        

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\VariantColor  $variantColor
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $variantColor = VariantColor::find($id);
    
        if (!$variantColor) {
            return response()->json([
                'success' => false,
                'message' => 'Variant color not found',
            ], 404);
        }
    
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'type' => 'required|in:single,multiple',
            'consumption_json' => 'nullable|json',
            'fabric_id' => 'required|array',
            'fabric_id.*.id' => 'required|integer',
            'fabric_id.*.name' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation failed',
            ], 422);
        }
    
        $variantColor->name = $request->name;
        $variantColor->type = $request->type;
        $variantColor->consumption_json = $request->consumption_json;
        $variantColor->save();
    
        // Sync the fabric relationship with the provided fabric IDs
        $fabricIds = collect($request->fabric_id)->pluck('id');
        $variantColor->fabric()->sync($fabricIds);
    
        // Reload the fabric relationship for the updated variant color
        $variantColor->load('fabric:id,name');
    
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $variantColor->id,
                'name' => $variantColor->name,
                'type' => $variantColor->type,
                'consumption_json' => $variantColor->consumption_json,
                'status' => $variantColor->status,
                'fabric' => $variantColor->fabric->map(function ($fabric) {
                    return [
                        'id' => $fabric->id,
                        'name' => $fabric->name,
                    ];
                }),
                'selected_fabric_ids' => $variantColor->fabric->pluck('id'),
            ],
            'message' => 'Variant color updated successfully',
        ]);
    }

     public function updateStatus(Request $request, $id)
     {
        $variantColor = VariantColor::find($id);
    
        if (!$variantColor) {
            return response()->json([
                'success' => false,
                'message' => 'Variant color not found',
            ], 404);
        }
    
        $currentStatus = $variantColor->status;
        $newStatus = $currentStatus === 1 ? 0 : 1;
    
        // Update the status in both variant_color and variant_color_combination tables
        $variantColor->status = $newStatus;
        $variantColor->save();
    
        $variantColor->load('fabric:id,name');
        $fabric = $variantColor->fabric->map(function ($fabric) {
            return [
                'id' => $fabric->id,
                'name' => $fabric->name,
            ];
        });
    
        $selectedFabricIds = $variantColor->fabric->pluck('id')->toArray();
    
        $message = $variantColor->status ? 'Variant color status enabled successfully' : 'Variant color status disabled successfully';
    
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $variantColor->only(['id', 'name', 'type', 'consumption_json', 'status', 'created_at', 'updated_at']) + [
                'fabric' => $fabric,
                'selected_fabric_ids' => $selectedFabricIds,
            ],
        ]);
    }
    
                    
            
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\VariantColor  $variantColor
     * @return \Illuminate\Http\Response
     */

     public function destroy($id)
     {
         $variantColor = VariantColor::find($id);
     
         if (!$variantColor) {
             return response()->json([
                 'success' => false,
                 'message' => 'Variant color not found',
             ], 404);
         }
     
         // Delete the variant_color_combination records associated with the variant color
         $variantColor->variantColorCombinations()->delete();
     
         // Delete the variant color
         $variantColor->delete();
     
         return response()->json([
             'success' => true,
             'message' => 'Variant color deleted successfully',
         ]);
     }
     
 
  
}
