<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\Yarns;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class YarnsController extends BaseController {

    public function index(Request $request) {
        try {
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $query = Yarns::query();
            $query->where('shop', $request->header('Shop'));
            if ($request->has('search')) {
                $query->where(DB::raw('lower(name)'), 'LIKE', "%" . strtolower($request->query('search')) . "%");
            }
//echo $query->toSql();
            $yarns = $query->paginate($limit);

            return response()->json([
                        'success' => true,
                        'message' => 'Yarns fetched successfully',
                        'data' => $yarns,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                            ], 500);
        }
    }

    public function store(Request $request) {
        $request->request->add(['shop' => $request->header('Shop')]);
        $validator = Validator::make($request->all(), Yarns::$rules, Yarns::$messages);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $sucessMessages = [];

            if ($errors->has('name')) {
                $sucessMessages = $errors->first('name');
            } elseif ($errors->has('status')) {
                $sucessMessages = $errors->first('status');
            }

            return response()->json(['success' => false, 'errors' => $errors, 'message' => $sucessMessages], 422);
        }

        // Validation passed, process the data and create the Yarns model instance
        $yarn = Yarns::create($request->all());

        return response()->json([
                    'success' => true,
                    'message' => 'Yarn created successfully',
                    'data' => $yarn
                        ], 201);
    }

    public function show($id) {
        $validator = Validator::make(['id' => $id], ['id' => 'required|exists:yarns,id']);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $errors
                            ], 422);
        }

        try {
            $yarn = Yarns::findOrFail($id);
            return response()->json([
                        'success' => true,
                        'message' => 'Yarn fetched successfully',
                        'data' => $yarn
                            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                        'success' => false,
                        'message' => 'Failed to fetch yarn',
                        'error' => $e->getMessage()
                            ], 500);
        }
    }

    public function update(Request $request, $id) {
        try {
            $yarn = Yarns::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Yarn not found'], 404);
        }

        $request->request->add(['shop' => $request->header('Shop')]);
        try {
            $yarn->update($request->all());
            $updatedYarn = Yarns::findOrFail($id);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Check if the error message contains the 'title' field
            if (str_contains($errorMessage, 'name')) {
                $errorMessage = 'The name field is required';
            }

            return response()->json(['success' => false, 'message' => $errorMessage], 422);
        }

        // Fetch the updated yarn from the database
        $updatedYarn = Yarns::find($id);

        $updatedData = [
            'name' => $updatedYarn->name,
            'status' => $updatedYarn->status
        ];

        return response()->json(['success' => true, 'message' => 'Yarn updated successfully', 'data' => $updatedData]);
    }

    public function destroy($id) {
        try {
            $yarn = Yarns::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Yarn not found'], 404);
        }

        $yarn->delete();

        return response()->json(['success' => true, 'message' => 'Yarn deleted successfully']);
    }

}
