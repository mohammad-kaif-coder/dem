<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\YarnColors;
use App\Models\Colors;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class YarnColorsController extends BaseController {

    public function index(Request $request) {
        try {
            $limit = $request->input('limit', 10);
            $page = $request->input('page', 1);
            $search = $request->input('search');

            $query = YarnColors::query();
            $query->where('shop', $request->header('Shop'));
            if ($search) {
                $query->where(DB::raw('lower(name)'), 'LIKE', "%" . strtolower($search) . "%");
            }
            if ($request->has('yarn_id') && $request->query('yarn_id') != '') {
                $query->where('yarn_id', $request->query('yarn_id'));
            }
            if ($request->has('type') && $request->query('type') != '') {
                $query->where('type', $request->query('type'));
            }

            $yarn_colors = $query->with('yarn')
                            ->with(['inventory' => function ($query) {
                                    // Select only the stock field from inventory
                                    $query->select('yarn_color_id', DB::raw('SUM(stock) as stock'));
                                    $query->groupBy('yarn_color_id');
                                }])
                            ->orderBy('yarn_id', 'DESC')
                            ->orderBy('type', 'DESC')
                            ->paginate($limit)->toArray();
//            print_R($yarn_colors);
//            exit;
            $low_stock_entity_count = 0;
            foreach ($yarn_colors['data'] as $key => $yarn_color) {

                if (isset($yarn_color['inventory'][0])) {
                    $inventory = $yarn_color['inventory'][0];
                    $inventory['stock'] = (float) $inventory['stock'];

                    unset($yarn_colors['data'][$key]['inventory']);
                    $yarn_colors['data'][$key]['inventory'] = $inventory;
                } else {
                    $inventory = [];
                    $inventory['stock'] = 0;
                    $yarn_colors['data'][$key]['inventory'] = null;
                }

                if ($yarn_color['type'] == 'multiple') {
                    $yarn_colors['data'][$key]['color_code'] = YarnColors::select('id', 'name', 'color_code')->whereIn('id', explode(',', $yarn_color['color_code']))->get()->toArray();
                    if ($request->has('low_stock') && $request->query('low_stock') == true) {
                        unset($yarn_colors['data'][$key]);
                    }
                } else {
                    $yarn_colors['data'][$key]['minimum_stock'] = (empty($yarn_colors['data'][$key]['minimum_stock']) || $yarn_colors['data'][$key]['minimum_stock'] == null) ? 0 : $yarn_colors['data'][$key]['minimum_stock'];
                    if ($inventory['stock'] < $yarn_colors['data'][$key]['minimum_stock']) {
                        $low_stock_entity_count++;
                    }

                    if ($request->has('low_stock') && $request->query('low_stock') == true) {
                        if ($inventory['stock'] > $yarn_colors['data'][$key]['minimum_stock']) {
                            unset($yarn_colors['data'][$key]);
                        }
                    }
                }
            }

            $yarn_colors['data'] = array_values($yarn_colors['data']);
            return response()->json([
                        'success' => true,
                        'message' => 'Yarn colors fetched successfully',
                        'data' => $yarn_colors,
                        'low_stock_entity_count' => $low_stock_entity_count,
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
        $validator = Validator::make($request->all(), YarnColors::$rules, YarnColors::$messages);

        if ($validator->fails()) {
            $errors = $validator->errors(); //print_R($errors);exit;
            $__ERRORS = $errors->toArray();
            $error_message = '';
            foreach ($__ERRORS as $ekey => $eval) {
                $error_message = (isset($eval[0]) ? $eval[0] : '');
                break;
            }

            return response()->json(['success' => false, 'errors' => $errors, 'message' => $error_message], 422);
        }


//        $a = [['name' => "red", "id" => 2], ["name" => "blue", "id" => 2]];
//print_R(json_encode($a));exit;
        // Validation passed, process the data and create the Yarns model instance
        if ($request->has('type') && $request->post('type') == 'multiple') {
            $color_code = $request->post('color_code');
            // $color_code = json_decode($request->post('color_code'), true);
            $request->merge(['color_code' => implode(',', array_column($color_code, 'id'))]);
        }
        $yarn_color = YarnColors::create($request->post());
        $yarn_color = YarnColors::where('id', $yarn_color->id)->with('yarn')->first();

        if ($yarn_color->type == 'multiple') {
            $yarn_color->color_code = YarnColors::select('id', 'name', 'color_code')->whereIn('id', explode(',', $yarn_color->color_code))->get()->toArray();
        }

        return response()->json([
                    'success' => true,
                    'message' => 'Yarn created successfully',
                    'data' => $yarn_color
                        ], 201);
    }

    public function show($id) {
        $validator = Validator::make(['id' => $id], ['id' => 'required|exists:yarn_colors,id']);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $errors
                            ], 422);
        }

        try {
            $yarn_color = YarnColors::where('id', $id)->with('yarn')->first();
            if ($yarn_color->type == 'multiple') {
                $yarn_color->color_code = YarnColors::select('id', 'name', 'color_code')->whereIn('id', explode(',', $yarn_color->color_code))->get()->toArray();
            }

            return response()->json([
                        'success' => true,
                        'message' => 'Yarn colors fetched successfully',
                        'data' => $yarn_color
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
            $yarn_color = YarnColors::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Yarn color not found'], 404);
        }

        $request->request->add(['shop' => $request->header('Shop')]);
        try {
            $rules = YarnColors::$rules;
            $rules['name'] = $rules['name'] . ',name,' . $id;
            $validationCertificate = Validator::make($request->post(), $rules);

            if ($request->has('type') && $request->post('type') == 'multiple') {
                // $color_code = json_decode($request->post('color_code'), true);
                $color_code = $request->post('color_code');
                $request->merge(['color_code' => implode(',', array_column($color_code, 'id'))]);
            }

            $yarn_color->update($request->post());
            $yarn_color = YarnColors::where('id', $id)->with('yarn')->first();

            if ($yarn_color->type == 'multiple') {
                $yarn_color->color_code = YarnColors::select('id', 'name', 'color_code')->whereIn('id', explode(',', $yarn_color->color_code))->get()->toArray();
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json(['success' => false, 'message' => $errorMessage], 422);
        }

        return response()->json(['success' => true, 'message' => 'Yarn updated successfully', 'data' => $yarn_color]);
    }

    public function destroy($id) {
        try {
            $yarn_color = YarnColors::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Yarn color not found'], 404);
        }

        $yarn_color->delete();

        return response()->json(['success' => true, 'message' => 'Yarn color deleted successfully']);
    }

}
