<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\VariantYarnColorConsumption;
use App\Models\Colors;
use App\Models\YarnColors;
use App\Models\VariantYarnMappings;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class VariantYarnColorConsumptionController extends BaseController {

    public function detail(Request $request) {
        echo "fsdfsd";
        exit;
        $variant_yarn_color_consumption;
        if ($request->has('yarn_id')) {
            $yarn_colors = YarnColors::where('yarn_id', $request->post('yarn_id'))->get();
            foreach ($yarn_colors as $key => $yarn_color) {
                if ($yarn_color->type == 'multiple') {
                    $yarn_colors[$key]->color_code = YarnColors::select('id', 'name', 'color_code')->whereIn('id', explode(',', $yarn_color->color_code))->get()->toArray();
                }
            }
            print_R($yarn_colors);
            exit;
        }
        return response()->json([
                    'success' => true,
                    'message' => 'Consumption fetched successfully',
                    'data' => $variant_yarn_color_consumption
                        ], 201);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), VariantYarnColorConsumption::$rules, YarnColors::$messages);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $sucessMessages = [];

            return response()->json(['success' => false, 'errors' => $errors, 'message' => ""], 422);
        }
//        print_R($request->post('consumption'));exit;

        $saved_consumption = [];
        foreach ($request->post('consumption') as $consumption) {
            $r = VariantYarnColorConsumption::updateOrCreate(
                            [
                                'variant_id' => $request->post('variant_id'),
                                'yarn_color_id' => $consumption['yarn_color_id'],
                            ],
                            [
                                'variant_id' => $request->post('variant_id'),
                                'yarn_id' => $request->post('yarn_id'),
                                'yarn_color_id' => $consumption['yarn_color_id'],
                                'consumption' => $consumption['consumption'],
                                'unit' => "kg",
            ]);

            if (isset($r->id)) {
                $saved_consumption[] = $consumption;
            }
        }


        return response()->json([
                    'success' => true,
                    'message' => 'Consumption saved successfully',
                    'data' => $saved_consumption
                        ], 201);
    }

    public function getConsumption(Request $request) {
        $variant_yarn_color_consumption = [];
        try {
            if ($request->has('yarn_id') && $request->has('variant_id')) {
                $yarn_mappings = VariantYarnMappings::where(
                                [
                                    'yarn_id' => $request->query('yarn_id'),
                                    'variant_id' => $request->query('variant_id')
                                ])->get()->toArray();
//print_R($yarn_mappings);exit;
                $yarn_colors = YarnColors::whereIn('id', array_column($yarn_mappings, 'yarn_color_id'))
                        ->with(['inventory' => function ($query) {
                                // Select only the stock field from inventory
                                $query->select('yarn_color_id', DB::raw('SUM(stock) as stock'));
                                $query->groupBy('yarn_color_id');
                            }])
                        ->get();
//                print_R($yarn_colors);
//                exit;
                foreach ($yarn_colors as $key => $yarn_color) {
                    if ($yarn_color->type == 'multiple') {
                        $__multiple_colors = YarnColors::select('id', 'name', 'color_code')->whereIn('id', explode(',', $yarn_color->color_code))->get();
                        foreach ($__multiple_colors as $__multiple_color) {
                            $consumption = VariantYarnColorConsumption::where(
                                            [
                                                'variant_id' => $request->query('variant_id'),
                                                'yarn_id' => $request->query('yarn_id'),
                                                'yarn_color_id' => $__multiple_color->id
                                            ])
                                    ->first();
                            $variant_yarn_color_consumption[$__multiple_color->id] = [
                                "yarn_color_id" => $__multiple_color->id,
                                "name" => $__multiple_color->name,
                                "consumption" => (isset($consumption->consumption) ? $consumption->consumption : '')
                            ];
                        }
                    } else {
                        if ($yarn_color->inventory->isNotEmpty()) {
                            $inventory = $yarn_color->inventory->first();
                            $__inventory = (float) $inventory->stock;
                        } else {
                            $__inventory = null;
                        }

                        $consumption = VariantYarnColorConsumption::where(
                                        [
                                            'variant_id' => $request->query('variant_id'),
                                            'yarn_color_id' => $yarn_color->id
                                        ])
                                ->first();
                        $variant_yarn_color_consumption[$yarn_color->id] = [
                            "yarn_color_id" => $yarn_color->id,
                            "name" => $yarn_color->name,
                            "consumption" => (isset($consumption->consumption) ? $consumption->consumption : ''),
                            "inventory" => $__inventory
                        ];
                    }
                }
            }

            return response()->json([
                        'success' => true,
                        'message' => 'Consumption fetched successfully',
                        'data' => array_values($variant_yarn_color_consumption)
                            ], 201);
        } catch (Exception $ex) {
            return response()->json([
                        'success' => false,
                        'message' => 'Failed to fetch consumption',
                        'error' => $e->getMessage()
                            ], 500);
        }
    }

}
