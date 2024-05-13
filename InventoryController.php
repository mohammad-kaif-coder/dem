<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Fabric;
use App\Models\YarnColors;
use Illuminate\Support\Facades\DB;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Console\Commands\UpdateInventory;
use Illuminate\Support\Facades\Artisan;

class InventoryController extends Controller {

    public function index(Request $request) {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', '');
        $fabricId = $request->input('fabric_id');
        $yarn_color_id = $request->input('yarn_color_id');

        $query = Inventory::query();

        if (!empty($search)) {
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('fabric_id', 'LIKE', "%$search%");
                $innerQuery->orWhere('stock', 'LIKE', "%$search%");
            });
        }

        if (!empty($fabricId)) {
            $query->where('fabric_id', $fabricId);
        }
        if (!empty($yarn_color_id)) {
            $query->where('yarn_color_id', $yarn_color_id);
        }

        // Order the results by the latest added fabric
        $query->orderByDesc('created_at');

        $inventories = $query->paginate($perPage);

        return response()->json([
                    'success' => true,
                    'data' => $inventories,
                        ], 200);
    }

    /**
     * Store a newly created inventory record in storage.
     */
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
                    'yarn_color_id' => 'required|numeric',
                    'stock' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            // Validation failed
            $errors = $validator->errors()->all();
            return response()->json([
                        'success' => false,
                        'message' => $errors,
                        'errors' => $errors,
                            ], 400);
        } else {
            // Convert the stock value to decimal
            $stock = round((float) $request->stock, 2);
            $yarn_color_id = $request->yarn_color_id;

            // Retrieve the yarn color
            $yarn_color = YarnColors::with('yarn')->find($yarn_color_id);

            if (!$yarn_color) {
                return response()->json([
                            'success' => false,
                            'message' => 'Yarn color not found'
                                ], 404);
            }

            // Calculate the total stock by summing the existing stock and the new stock
            $totalStock = round((float) $yarn_color->inventory->sum('stock') + $stock, 2);

            // Create a new inventory entry and save it in the database
            $newInventory = new Inventory([
                'yarn_color_id' => $yarn_color_id,
                'stock' => $stock,
                'status' => 1,
            ]);
            $newInventory->save();

            $data = $yarn_color->toArray();
            $data['inventory'] = [
                'stock' => $totalStock,
            ];

//            Artisan::call('UpdateInventory', [
//                "shop" => $request->header('Shop'),
//                "yarn_color_id" => $yarn_color_id
//            ]);

            Artisan::queue('UpdateInventory', [
                "shop" => $request->header('Shop'),
                "yarn_color_id" => $yarn_color_id
            ]);

            \Log::info('UpdateInventory is called from Inventory Controller at ' . date('Y-d-m H:i:s'));

            return response()->json([
                        'success' => true,
                        'message' => 'Stock added successfully',
                        'data' => [$data],
                            ], 201);
        }
    }

    /**
     * Display the specified inventory record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                        'success' => false,
                        'message' => 'Inventory not found',
                            ], 404);
        }

        return response()->json([
                    'success' => true,
                    'data' => $inventory,
                        ], 200);
    }

    /**
     * Remove the specified inventory record from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                        'success' => false,
                        'message' => 'Inventory not found',
                            ], 404);
        }

        $inventory->delete();

        return response()->json([
                    'success' => true,
                    'message' => 'Inventory deleted successfully',
                        ], 200);
    }

}
