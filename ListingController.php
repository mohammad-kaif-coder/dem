<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ShopifyProducts;
use App\Models\ShopifyProductsVariants;
use App\Models\ShopifyProductsImages;
use App\Models\Consumption;
use App\Models\YarnColors;
use App\Models\VariantYarnMappings;
use Illuminate\Support\Facades\DB;
use App\Models\VariantYarnColorConsumption;

class ListingController extends BaseController {

    use AuthorizesRequests;
    use ValidatesRequests;

    /**
     * this function is for listing 
     * @param 
     */
    public function index(Request $request) {
        $response = [];
        $limit = ($request->has('limit') ? $request->query('limit') : 10);

        if ($request->query('type') == 'products') {
            $query = ShopifyProducts::select('*');
            $query->where(['shop' => $request->header('shop')]);
            if ($request->has('search') && !empty($request->query('search'))) {
                $query->where(function ($query) use ($request) {
                    $query->where('product_id', "like", "%{$request->query('search')}%");
                    $query->orWhere(DB::raw('lower(title)'), "like", "%" . strtolower($request->query('search')) . "%");
                    $query->orWhere(DB::raw('lower(handle)'), "like", "%" . strtolower($request->query('search')) . "%");
                });
            }
            if ($request->has('product_type') && !empty($request->query('product_type'))) {
                $query->where('shopify_products.product_type', $request->query('product_type'));
            }
            $query->get();
            $query->withCount('variants');
            $query->with('variants');
            $query->with('yarn');
            $query->orderBy('product_id', 'DESC');

            $response[$request->query('type')] = $query->with('image')->paginate($limit);
        } elseif ($request->query('type') == 'products_variants') {
            $query = ShopifyProductsVariants::query()
                    ->leftJoin('shopify_products', 'shopify_products.product_id', '=', 'shopify_products_variants.product_id');

            if ($request->has('product_id') && !empty($request->query('product_id'))) {
                $query->where('shopify_products.product_id', $request->query('product_id'));
            }

            if ($request->has('search') && !empty($request->query('search'))) {
                $query->where(function ($query) use ($request) {
                    $query->where('shopify_products_variants.title', 'like', "%{$request->query('search')}%");
                    $query->orWhere('sku', 'like', "%{$request->query('search')}%");
                });
            }

            $variants = $query->paginate($limit);

            // Fetch consumptions for each variant
            foreach ($variants as $variant) {
                $variant->load('consumptions');

                // Retrieve fabric consumptions for each variant
                $fabric = [];
                foreach ($variant->consumptions as $consumption) {
                    $fabric[] = [
                        'id' => $consumption->fabric_id,
                        'name' => $consumption->fabric->name,
                        'consumption' => number_format($consumption->consumption, 2),
                    ];
                }

                $variant->fabric = $fabric;
                unset($variant->consumptions);
            }

            // Retrieve only one image for each variant
            $variants->getCollection()->each(function ($variant) {
                $variant->image = $variant->images()->first();
                unset($variant->images);
            });

            $response[$request->query('type')] = $variants;
        }

        return response()->json([
                    'success' => true,
                    'message' => 'Products fetched successfully',
                    'data' => $response,
        ]);
    }

    public function show(Request $request) {
        $type = $request->query('type');

        $model_name = "App\Models\Shopify" . ucfirst($type);
        $model = new $model_name();

        $query = $model->where(
                [
                    'product_id' => $request->query('id')
                ]
        );
        if ($type == 'products') {
            $query->withCount('variants');
            $query->with('variants');
            $query->with('yarn');
        }
        $response = $query->first();

        $yarn_id = isset($response->yarn->yarn_id) ? $response->yarn->yarn_id : null;
//        print_R($yarn_id);
        if (isset($response->variants)) {
            foreach ($response->variants as $key => $variant) {

                //echo $variant->title;
                $variant_title = explode('/', $variant->title);
                $variant_title = end($variant_title);

                $associated_yarn_colors = YarnColors::where([
                            'yarn_id' => $yarn_id,
                            'shop' => $request->header('shop')
                        ])
                        ->with(['inventory' => function ($query) {
                                // Select only the stock field from inventory
                                $query->select('yarn_color_id', DB::raw('SUM(stock) as stock'));
                                $query->groupBy('yarn_color_id');
                            }])
                        ->get();
                $associated_yarn_colors = $associated_yarn_colors->toArray();
                $associated_yarn_colors_name = array_column($associated_yarn_colors, 'name');
                $associated_yarn_colors_name = array_map('strtolower', $associated_yarn_colors_name);

                $search_key = array_search(strtolower(trim($variant_title)), $associated_yarn_colors_name);

                if (is_numeric($search_key)) {

                    $r = VariantYarnMappings::updateOrCreate(['variant_id' => $variant->variant_id], [
                                'variant_id' => $variant->variant_id,
                                'yarn_id' => $yarn_id,
                                'yarn_color_id' => $associated_yarn_colors[$search_key]['id'],
                    ]);

                    $response->variants[$key]->mapped_with_yarn_consumption = true;

                    /**
                     * data collection for type single
                     */
                    if ($associated_yarn_colors[$search_key]['type'] == "single") {
                        /**
                         * Get color inventory
                         */
                        if (isset($associated_yarn_colors[$search_key]['inventory'][0]['stock']) && !empty($associated_yarn_colors[$search_key]['inventory'][0]['stock'])) {
                            $__inventory = (float) $associated_yarn_colors[$search_key]['inventory'][0]['stock'];
                        } else {
                            $__inventory = null;
                        }

                        /**
                         * Get color consumption
                         */
                        $consumption = VariantYarnColorConsumption::where(
                                        [
                                            'variant_id' => $variant->variant_id,
                                            'yarn_id' => $yarn_id,
                                            'yarn_color_id' => $associated_yarn_colors[$search_key]['id']
                                        ])
                                ->first();
                        $response->variants[$key]->consumption = [
                            [
                                "yarn_color_id" => $associated_yarn_colors[$search_key]['id'],
                                "name" => $associated_yarn_colors[$search_key]['name'],
                                "consumption" => (isset($consumption['consumption']) ? $consumption['consumption'] : ''),
                                'inventory' => $__inventory
                            ]
                        ];
                    } else {
                        $__multiple_colors = YarnColors::select('id', 'name', 'color_code')->whereIn('id', explode(',', $associated_yarn_colors[$search_key]['color_code']))->get();
                        $loop_count = 1;
                        foreach ($__multiple_colors as $__multiple_color) {
                            $multi_search_key = array_search(strtolower(trim($__multiple_color->name)), $associated_yarn_colors_name);
                            if (isset($associated_yarn_colors[$multi_search_key]['inventory'][0]['stock']) && !empty($associated_yarn_colors[$multi_search_key]['inventory'][0]['stock'])) {
                                $__inventory = (float) $associated_yarn_colors[$multi_search_key]['inventory'][0]['stock'];
                            } else {
                                $__inventory = null;
                            }

                            $consumption = VariantYarnColorConsumption::where(
                                            [
                                                'variant_id' => $variant->id,
                                                'yarn_id' => $yarn_id,
                                                'yarn_color_id' => $__multiple_color->id
                                            ])
                                    ->first();

                            $__consumption_array[] = [
                                "yarn_color_id" => $__multiple_color->id,
                                "name" => $__multiple_color->name,
                                "consumption" => (isset($consumption['consumption']) ? $consumption['consumption'] : ''),
                                'inventory' => $__inventory
                            ];
                        }
                        $response->variants[$key]->consumption = $__consumption_array;

//                    print_R($response->variants[$key]->toArray());
                    }
                }


//                print_R($response->variants[$key]);
//            exit;
//            print_R($associated_yarn_colors);
//            exit;
//            echo $title;
//            print_R($variant->toArray());
//            exit;
            }
        }
//        print_R($response);
//        exit;
        return response()->json([
                    'success' => true,
                    'message' => 'Product yarn fetched successfully',
                    'data' => $response,
        ]);
    }

}
