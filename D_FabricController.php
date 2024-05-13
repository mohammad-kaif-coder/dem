<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Support\Facades\DB;
use App\Models\Fabric;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;


class D_FabricController extends Controller
{
    
    
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Number of records per page, default is 10
        $limit = $request->input('limit'); // Limit the total number of records
        $search = $request->input('search'); // Search keyword
        $status = $request->input('status'); // Status filter
    
        $query = Fabric::query();
    
        // Perform search if a search keyword is provided
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('type', 'LIKE', "%{$search}%")
                    ->orWhere('color', 'LIKE', "%{$search}%")
                    ->orWhere('color_code', 'LIKE', "%{$search}%");
            });
        }
    
        // Filter fabrics by status
        if ($status === '1') {
            $query->where('status', true);
        } elseif ($status === '0') {
            $query->where('status', false);
        } else {
            // By default, show all fabrics
            $query->where(function ($query) {
                $query->where('status', true)
                    ->orWhere('status', false);
            });
        }
    
        // Sort fabrics by the latest created on top
        $query->latest();
    
        // Apply pagination
        $fabrics = $query->with(['inventory' => function ($query) {
            // Select only the stock field from inventory
            $query->select('fabric_id', DB::raw('SUM(stock) as stock'));
            $query->groupBy('fabric_id');
        }])->paginate($perPage)->withQueryString(); // Preserve query string in pagination links
    
        // Transform the collection to the desired JSON structure
        $formattedFabrics = [];
        foreach ($fabrics as $fabric) {
            $formattedFabric = $fabric->toArray();
    
            if ($fabric->inventory->isNotEmpty()) {
                $inventory = $fabric->inventory->first();
                $inventory->stock = (float) $inventory->stock;
                $formattedFabric['inventory'] = (object) $inventory->toArray();
            } else {
                $formattedFabric['inventory'] = null;
            }
    
            $formattedFabric['status'] = $formattedFabric['status'] ? 1 : 0; // Convert status to 0 or 1
    
            $formattedFabrics[] = $formattedFabric;
        }
    
        if ($limit) {
            $formattedFabrics = array_slice($formattedFabrics, 0, $limit); // Limit the number of records
        }
    
        $pagination = [
            'current_page' => $fabrics->currentPage(),
            'last_page' => $fabrics->lastPage(),
            'per_page' => $fabrics->perPage(),
            'total' => $fabrics->total(),
        ];
    
        $data = [
            'current_page' => $fabrics->currentPage(),
            'data' => $formattedFabrics,
            'first_page_url' => $fabrics->url(1),
            'from' => $fabrics->firstItem(),
            'last_page' => $fabrics->lastPage(),
            'last_page_url' => $fabrics->url($fabrics->lastPage()),
            'per_page' => $fabrics->perPage(),
            'total' => $fabrics->total(),
        ];
    
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Fabrics retrieved successfully'
        ]);
    }
        
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'type' => 'required',
            'color' => 'required',
            'color_code' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ]);
    
        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessage = $errors->first();
    
            return response()->json([
                'success' => false,
                'errors' => $errors,
                'message' => $errorMessage
            ], 422);
        }
    
        // Process the data and create the Fabric model instance
        $fabricData = $request->all();
        $fabricData['status'] = isset($fabricData['status']) ? (int) $fabricData['status'] : 1;
        $fabric = Fabric::create($fabricData);
    
        // Convert status to 1 or 0
        $fabric->status = (int) $fabric->status;
    
        // Create an empty inventory object
        $inventory = (object) [];
    
        // Assign the empty inventory object to the fabric
        $fabric->inventory = $inventory;
    
        return response()->json([
            'success' => true,
            'data' => $fabric,
            'message' => 'Fabric saved successfully'
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $fabric = Fabric::find($id);
    
        if (!$fabric) {
            return response()->json([
                'success' => false,
                'message' => 'Fabric not found'
            ], 404);
        }
    
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'type' => 'required',
            'color' => 'required',
            'color_code' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ]);
    
        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessage = $errors->first();
    
            return response()->json([
                'success' => false,
                'errors' => $errors,
                'message' => $errorMessage
            ], 422);
        }
    
        $fabric->name = $request->name;
        $fabric->type = $request->type;
        $fabric->color = $request->color;
        $fabric->color_code = $request->color_code;
        $fabric->save();
    
        // Update inventory if provided in the request
        if ($request->has('stock')) {
            $stock = (float) $request->stock;
    
            $inventory = $fabric->inventory()->firstOrNew(['fabric_id' => $fabric->id]);
            $inventory->stock = $stock;
            $inventory->status = 1;
            $inventory->save();
        }
    
        $formattedFabric = $fabric->toArray();
    
        // Calculate the total stock by summing the stock of all inventory entries
        $totalStock = $fabric->inventory->sum('stock');
    
        // Retrieve the latest inventory
        $latestInventory = $fabric->inventory()->latest()->first();
    
        if ($latestInventory) {
            $formattedFabric['inventory'] = [
                'id' => $latestInventory->id,
                'fabric_id' => $latestInventory->fabric_id,
                'stock' => $totalStock,
                'status' => $latestInventory->status,
                'created_at' => $latestInventory->created_at,
                'updated_at' => $latestInventory->updated_at
            ];
        } else {
            $formattedFabric['inventory'] = null;
        }
    
        $formattedFabric['status'] = $formattedFabric['status'] ? 1 : 0; // Convert status to 0 or 1
    
        return response()->json([
            'success' => true,
            'data' => $formattedFabric,
            'message' => 'Fabric updated successfully'
        ]);
    }
        
    public function toggleStatus($id)
    {
        $fabric = Fabric::find($id);
    
        if (!$fabric) {
            return response()->json([
                'success' => false,
                'message' => 'Fabric not found'
            ], 404);
        }
    
        $fabric->status = !$fabric->status;
        $fabric->save();
    
        $formattedFabric = $fabric->toArray();
    
        // Update inventory status if it exists
        if ($fabric->inventory()->exists()) {
            $stock = $fabric->inventory()->sum('stock');
            $formattedStock = $stock;
            if (strpos($formattedStock, '.') !== false) {
                $formattedStock = rtrim($formattedStock, '0');
                $formattedStock = rtrim($formattedStock, '.');
            }
            $formattedFabric['inventory'] = [
                'stock' => $formattedStock
            ];
        } else {
            $formattedFabric['inventory'] = null;
        }
    
        $message = $fabric->status ? 'Fabric status enabled successfully' : 'Fabric status disabled successfully';
        $formattedFabric['status'] = $fabric->status ? 1 : 0;
    
        return response()->json([
            'success' => true,
            'data' => $formattedFabric,
            'message' => $message
        ]);
    }
    
    
    
    public function destroy($id)
    {
        $fabric = Fabric::find($id);

        if (!$fabric) {
            return response()->json([
                'success' => false,
                'message' => 'Fabric not found'
            ], 404);
        }

        $fabric->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fabric deleted successfully'
        ]);
    }

}
