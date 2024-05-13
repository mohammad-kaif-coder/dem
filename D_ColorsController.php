<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\Colors;
use App\Http\Resources\ColorResource;
use Illuminate\Support\Facades\Validator;


class D_ColorsController extends BaseController
{
 
    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $page = $request->input('page', 1);
            $search = $request->input('search');
    
            $query = Colors::query();
    
            if ($search) {
                $query->where('title', 'LIKE', "%$search%");
            }
    
            $colors = $query->paginate($limit);
    
            return response()->json([
                'success' => true,
                'message' => 'Colors fetched successfully',
                'data' => $colors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
            
        public function store(Request $request)
    {
        $validator = Validator::make($request->all(), Colors::$rules, Colors::$messages);
    
        if ($validator->fails()) {
            $errors = $validator->errors();
            $sucessMessages = [];
        
            if ($errors->has('title')) {
                $sucessMessages = $errors->first('title');
            } elseif ($errors->has('code')) {
                $sucessMessages = $errors->first('code');
            } elseif  ($errors->has('quantity')) {
                $sucessMessages = $errors->first('quantity');
            } 
    
            return response()->json(['success' => false, 'errors' => $errors, 'message' => $sucessMessages], 422);
        }
    
        // Validation passed, process the data and create the Colors model instance
        $color = Colors::create($request->all());
    
        return response()->json(['success' => true, 'data' => new ColorResource($color), 'message' => 'Color saved successfully']);
    }   
           
    public function show($id)
    {
        $validator = Validator::make(['id' => $id], ['id' => 'required|exists:colors,id']);
    
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ], 422);
        }
    
        try {
            $color = Colors::findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'Color fetched successfully',
                'data' => new ColorResource($color)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch color',
                'error' => $e->getMessage()
            ], 500);
        }
    }        
    
    public function update(Request $request, $id)
    {
        try {
            $color = Colors::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Color not found'], 404);
        }
    
        try {
            $color->update($request->all());
            $updatedColor = Colors::findOrFail($id);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
    
            // Check if the error message contains the 'title' field
            if (str_contains($errorMessage, 'title')) {
                $errorMessage = 'The title field is required';
            }
    
            return response()->json(['success' => false, 'message' => $errorMessage], 422);
        }
    
        // Fetch the updated color from the database
        $updatedColor = Colors::find($id);
    
        $updatedData = [
            'title' => $updatedColor->title,
            'code' => $updatedColor->code,
            'quantity' => $updatedColor->quantity,
        ];
    
        return response()->json(['success' => true, 'message' => 'Color updated successfully', 'data' => $updatedData]);
    }
            
    public function destroy($id)
    {
        try {
            $color = Colors::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Color not found'], 404);
        }
    
        $color->delete();
    
        return response()->json(['success' => true, 'message' => 'Color deleted successfully']);
    }    
}
