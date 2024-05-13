<?php

namespace App\Http\Controllers\Api\v1;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Validator;
use App\Models\Category;

class D_CategoriesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 10); // Default limit is 10 if not provided in the request
            $search = $request->input('search'); // Get the search query from the request
            
            $categories = Category::query();
    
            // Check if search query is provided
            if ($search) {
                // Apply the search filter to the query
                $categories->where('title', 'LIKE', '%' . $search . '%');
            }
    
            $categories = $categories->paginate($limit);
    
            return response()->json([
                'success' => true,
                'message' => 'Categories fetched successfully',
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('title'),
                'errors' => $validator->errors()
            ], 422);
        }
    
        $category = Category::create([
            'title' => $request->input('title'),
        ]);
    
        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category retrieved successfully',
            'data' => $category
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessages = [];

            if ($errors->has('title')) {
                $errorMessages[] = $errors->first('title');
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessages,
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $category->update([
            'title' => $request->input('title'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

}
