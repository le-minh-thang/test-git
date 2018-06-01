<?php

namespace App\Http\Controllers;

use DB;
use App\Product;
use Carbon\Carbon;
use App\ProductSize;
use App\ProductColor;
use App\MasterItemType;
use App\PrinttyProduct;
use App\ProductColorSide;
use App\UpTPrinttyProduct;
use App\PrinttyProductSize;
use App\PrinttyProductColor;
use App\UpTPrinttyProductSize;
use App\UpTPrinttyProductColor;
use App\PrinttyProductColorSide;
use App\UpTPrinttyProductColorSide;

class InsertController extends Controller
{
    private $_dateTime;

    public function __construct()
    {
        $this->_dateTime = Carbon::now()->format('Y-m-d H:i:s');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        ini_set('max_execution_time', 666);
        set_time_limit(666);
        ini_set('memory_limit', '2048M');
        try {
            $productInserts                 = [];
            $productSizeInserts             = [];
            $productColorInserts            = [];
            $productColorSideInserts        = [];
            $printtyProductInserts          = [];
            $printtyProductSizeInserts      = [];
            $printtyProductColorInserts     = [];
            $printtyProductColorSideInserts = [];

            $printtyProducts = [
                'PrinttyProduct'          => 'UpTPrinttyProduct',
                'PrinttyProductSize'      => 'UpTPrinttyProductSize',
                'PrinttyProductColor'     => 'UpTPrinttyProductColor',
                'PrinttyProductColorSide' => 'UpTPrinttyProductColorSide',
            ];

            $lastProductId      = Product::orderBy('id', 'desc')->first()->id + 1;
            $lastProductColorId = ProductColor::orderBy('id', 'desc')->first()->id + 1;

            $masterItemTypes = MasterItemType::select('name as title', 'item_code as code')
                ->pluck('title', 'code')
//                ->whereIn(['IT413', 'IT414', 'IT415', 'IT416', 'IT417', 'IT418', 'IT419', 'IT420', 'IT421', 'IT422', 'IT423'])
                ->toArray();
            $products        = Product::select('title', 'code')->pluck('title', 'code')->toArray();
            $diffs           = array_diff_assoc($masterItemTypes, $products);

            $lastProductSizeOrder  = ProductSize::orderBy('order', 'desc')->first()->order + 1;
            $lastProductColorOrder = ProductColor::orderBy('order', 'desc')->first()->order;
            foreach ($diffs as $code => $name) {
                $item = MasterItemType::with('itemSubs', 'itemSizes', 'itemSubs.itemSubSides', 'printtyProduct', 'printtyProductColors', 'printtyProductColorSides', 'printtyProductSizes')
                    ->where('name', $name)
                    ->where('item_code', $code)
                    ->first();

                $productInserts[] = $this->_generateProduct($item, $lastProductId);
                var_dump("generate the product $lastProductId");
                $this->generateProductSizes($productSizeInserts, $lastProductId, $item->itemSizes, $lastProductSizeOrder);

                foreach ($item->itemSubs as $itemSub) {
                    $productColorInserts[] = $this->generateProductColors($itemSub, $lastProductId, $lastProductColorId, $lastProductColorOrder);
                    foreach ($itemSub->itemSubSides as $itemSubSide) {
                        $productColorSideInserts[] = $this->generateProductColorSides($itemSubSide, $lastProductColorId, $itemSub, $item);
                    }

                    $lastProductColorId += 1;
                }

                $lastProductId += 1;
            }

            // Insert printty product
            foreach ($printtyProducts as $printtyProductTableName => $upTPrinttyProductTableName) {
                $rinttyProductClass       = "\App\\{$printtyProductTableName}";
                $upTPrinttyProductClass   = "\App\\{$upTPrinttyProductTableName}";
                $printtyProductTableIds   = $rinttyProductClass::select('id')->pluck('id', 'id')->toArray();
                $upTrinttyProductTableIds = $upTPrinttyProductClass::select('id')->pluck('id', 'id')->toArray();
                $diffPrinttyProducts      = array_diff_assoc($upTrinttyProductTableIds, $printtyProductTableIds);
                if (!empty($diffPrinttyProducts)) {
                    ${lcfirst($printtyProductTableName) . 'Inserts'} = $upTPrinttyProductClass::whereIn('id', $diffPrinttyProducts)->get()->toArray();
                }
            }

            DB::beginTransaction();

            Product::insert($productInserts);
            ProductSize::insert($productSizeInserts);
            ProductColor::insert($productColorInserts);
            ProductColorSide::insert($productColorSideInserts);
            // Insert printty product
            PrinttyProduct::insert($printtyProductInserts);
            PrinttyProductSize::insert($printtyProductSizeInserts);
            PrinttyProductColor::insert($printtyProductColorInserts);
            PrinttyProductColorSide::insert($printtyProductColorSideInserts);

            DB::commit();
            dd('done');
        } catch (\Exception $exception) {
            DB::rollBack();
            var_dump("something went wrong");
            dd($exception);
        }
    }

    private function generateProductColorSides($itemSubSide, $lastProductColorId, $itemSub, $item)
    {
        $order = null;
        if ($itemSub->color == '#ffffff' || $itemSub->color == '#FFFFFF') {
            if ($itemSubSide->title == '表' || $itemSubSide->title == '表裏同じ' || $itemSubSide->title == '左前' || $itemSubSide->title == '右前') {
                $printPriceCost = $itemSub->cost1;
                $order          = 1;
            } else if ($itemSubSide->title == '裏' || $itemSubSide->title == '左後' || $itemSubSide->title == '右後') {
                $printPriceCost = $itemSub->cost2;
                $order          = 2;
            } else {
                $printPriceCost = $itemSub->cost3;
                if ($itemSubSide->title == '左袖') {
                    $order = 3;
                } else if ($itemSubSide->title == '右袖') {
                    $order = 4;
                }
            }

            // Get print price for the product
            if (isset($order)) {
                $orderPrice = $order;
            } else {
                $orderPrice = 6;
            }

            $printPriceCost = $this->_getPrintPrice($item, $orderPrice, $printPriceCost, true);
        } else {
            if ($itemSubSide->title == '表' || $itemSubSide->title == '表裏同じ' || $itemSubSide->title == '左前' || $itemSubSide->title == '右前') {
                $printPriceCost = $itemSub->cost1;
                $order          = 1;
            } else if ($itemSubSide->title == '裏' || $itemSubSide->title == '左後' || $itemSubSide->title == '右後') {
                $printPriceCost = $itemSub->cost2;
                $order          = 2;
            } else {
                $printPriceCost = $itemSub->cost3;
                if ($itemSubSide->title == '左袖') {
                    $order = 3;
                } else if ($itemSubSide->title == '右袖') {
                    $order = 4;
                }
            }

            // Get print price for the product
            if (isset($order)) {
                $orderPrice = $order;
            } else {
                $orderPrice = 0;
            }
            $printPriceCost = $this->_getPrintPrice($item, $orderPrice, $printPriceCost);
        }

        if ($itemSubSide->state == 1) {
            $delete = 0;
        } else {
            $delete = 1;
        }

        return [
            'title'            => $itemSubSide->title,
            'product_color_id' => $lastProductColorId,
            'is_main'          => $itemSubSide->is_main,
            'content'          => $itemSubSide->content,
            'image_url'        => $itemSubSide->image_url,
            'preview_url'      => $itemSubSide->preview_url,
            'print_price'      => $printPriceCost,
            'is_deleted'       => $delete,
            'order'            => $order,
            'created_at'       => $itemSubSide->created,
            'updated_at'       => $itemSubSide->modified,
            'content_print'    => $itemSubSide->content_print,
        ];
    }


    private function generateProductColors($itemSub, $lastProductId, $lastProductColorId, &$lastProductColorOrder)
    {
        if ($itemSub->state == 1) {
            $delete = 0;
        } else {
            $delete = 1;
        }
        $lastProductColorOrder += 1;
        return [
            'id'         => $lastProductColorId,
            'title'      => $itemSub->name,
            'value'      => $itemSub->color,
            'code'       => $itemSub->item_code,
            'product_id' => $lastProductId,
            'is_main'    => $itemSub->is_main,
            'is_deleted' => $delete,
            'order'      => $lastProductColorOrder,
            'created_at' => $itemSub->created,
            'updated_at' => $itemSub->modified,
        ];
    }

    private function _generateProduct($item, $lastProductId)
    {
        if ($item->state == 1) {
            $delete = 0;
        } else {
            $delete = 1;
        }

        //    up t category -> budget category
        //    22 -> 24
        //    23 -> 25
        //    24 -> 26
        //    25 -> 27
        //    26 -> 28
        //    27 -> 22
        //    28 -> 23
        if ($item->category_id == 22) {
            $category = 24;
        } else if ($item->category_id == 23) {
            $category = 25;
        } else if ($item->category_id == 24) {
            $category = 26;
        } else if ($item->category_id == 25) {
            $category = 27;
        } else if ($item->category_id == 26) {
            $category = 28;
        } else if ($item->category_id == 27) {
            $category = 22;
        } else if ($item->category_id == 28) {
            $category = 23;
        } else {
            $category = $item->category_id;
        }

        $prices    = $this->_getProductPrices($item);
        $price     = $prices['price'];
        $toolPrice = $prices['tool_price'];

        return [
            'id'               => $lastProductId,
            'category_id'      => $category,
            'title'            => $item->name,
            'code'             => $item->item_code,
            'price'            => $price,
            'is_main'          => $item->is_main,
            'is_deleted'       => $delete,
            'order'            => $item->order,
            'created_at'       => $item->created,
            'updated_at'       => $item->modified,
            'tool_price'       => $toolPrice,
            'color_total'      => $item->color_total,
            'size'             => $item->size,
            'sale_price'       => $item->sale_price,
            'item_code_nomial' => $item->item_code_nominal,
            'material'         => $item->material,
            'maker'            => $item->maker,
        ];
    }

    /**
     * @param $item
     * @return array
     */
    private function _getProductPrices($item)
    {
        if ($item->id == 'IT413' || $item->id == 'IT416') {
            $price = $toolPrice = 1300;
        } else if ($item->id == 'IT414' || $item->id == 'IT415') {
            $price = $toolPrice = 1100;
        } else if ($item->id == 'IT417') {
            $price = $toolPrice = 650;
        } else if ($item->id == 'IT418') {
            $price     = 1400;
            $toolPrice = 2400;
        } else if ($item->id == 'IT419') {
            $price     = 1000;
            $toolPrice = 2000;
        } else if ($item->id == 'IT420' || $item->id == 'IT422' || $item->id == 'IT423') {
            $price     = 1600;
            $toolPrice = 2600;
        } else if ($item->id == 'IT421') {
            $price     = 2300;
            $toolPrice = 3300;
        } else if ($item->id == 'IT409') {
            $price = $toolPrice = 4000;
        } else if ($item->id == 'IT410') {
            $price = $toolPrice = 3800;
        } else if ($item->id == 'IT411') {
            $price = $toolPrice = 900;
        } else if ($item->id == 'IT412') {
            $price = $toolPrice = 1000;
        } else {
            $price     = $item->item_price;
            $toolPrice = $item->tool_price;
        }

        return [
            'price'      => $price,
            'tool_price' => $toolPrice,
        ];
    }

    /**
     * @param $productSizeInserts
     * @param $lastProductId
     * @param $itemSizes
     * @param $lastProductSizeOrder
     */
    private function generateProductSizes(&$productSizeInserts, $lastProductId, $itemSizes, &$lastProductSizeOrder)
    {
        foreach ($itemSizes as $itemSize) {
            if ($itemSize->state == 1) {
                $delete = 0;
            } else {
                $delete = 1;
            }
            $productSizeInserts[] = [
                'product_id' => $lastProductId,
                'title'      => $itemSize->name,
                'is_main'    => $itemSize->is_main,
                'is_deleted' => $delete,
                'code'       => $itemSize->item_code,
                'order'      => $lastProductSizeOrder,
                'created_at' => $itemSize->created,
                'updated_at' => $itemSize->modified,
            ];
            $lastProductSizeOrder += 1;
        }
    }

    /**
     * @param $item
     * @param $order
     * @param $printPrice
     * @param bool $isWhite
     * @return int
     */
    private function _getPrintPrice($item, $order, $printPrice, $isWhite = false)
    {
        if ($item->id == 'IT413' || $item->id == 'IT414' || $item->id == 'IT415' || $item->id == 'IT416' || $item->id == 'IT417') {
            if ($order == 1) {
                return 0;
            }
        }

        if ($item->id == 'IT418' || $item->id == 'IT419' || $item->id == 'IT420' || $item->id == 'IT420' || $item->id == 'IT421' || $item->id == 'IT422' || $item->id == 'IT423') {
            if ($order == 1 || $order == 2) {
                $printPrice = 1000;
            }
        } else if ($item->id == 'ドライコットンタッチ ラウンドテールTシャツ') {
            if ($isWhite) {
                if ($order == 1) {
                    $printPrice = 1000;
                } else if ($order == 2) {
                    $printPrice = 1000;
                } else {
                    $printPrice = 650;
                }
            } else {
                if ($order == 1) {
                    $printPrice = 1800;
                } else if ($order == 2) {
                    $printPrice = 1800;
                } else {
                    $printPrice = 1400;
                }
            }
        }

        return $printPrice;
    }

//    public function insertFromPrintty()
//    {
//        $printtyProductInserts          = [];
//        $printtyProductSizeInserts      = [];
//        $printtyProductColorInserts     = [];
//        $printtyProductColorSideInserts = [];
//
//        $printtyPrinttyProducts = \App\PrinttyModels\Product::select('products.id', 'products_linked_codes.code as product_code')
//            ->join('products_linked_codes', 'products.id', '=', 'products_linked_codes.product_id')
//            ->where('products_linked_codes.is_deleted', 0)
//            ->where('products.id', '>', 320)
//            ->pluck('product_code', 'product.id')->toArray();
//
//        $printtyProducts = PrinttyProduct::select('id', 'product_code')->pluck('product_code', 'id')->toArray();
//
//        $diffs = array_diff_assoc($printtyPrinttyProducts, $printtyProducts);
//
//        if (count($diffs)) {
//            $printtyPrinttyProducts = \App\PrinttyModels\Product::select('products.id as id', 'products_linked_codes.code as product_code')
//                ->join('products_linked_codes', 'products.id', '=', 'products_linked_codes.product_id')
//                ->where('products_linked_codes.is_deleted', 0)
//                ->where('products.id', '>', 320)
//                ->whereIn('products.id', array_flip($diffs))
//                ->with([
//                    'productSizes',
//                    'productColors' => function ($q) {
//                        $q->select('products_colors_linked_codes.code as products_color_code', 'products_colors.id', 'products_colors.created_at', 'products_colors.updated_at')
//                            ->with([
//                                'productColorSides' => function ($q) {
//                                    $q->select('products_colors_linked_codes.code as products_color_code', 'products_colors_sides.id', 'products_colors_sides.created_at', 'products_colors_sides.updated_at')
//                                        ->join('products_colors_linked_codes', 'products_colors_linked_codes.product_color_id ', '=', 'products_colors_sides.id');
//                                },
//                            ])
//                            ->join('products_colors_linked_codes', 'products_colors_linked_codes.product_color_id', '=', 'products_colors .id');
//                    },
//                ])
//                ->get();
//            if ($printtyPrinttyProducts->count()) {
//                foreach ($printtyPrinttyProducts as $printtyPrinttyProduct) {
//                    $this->_generatePrinttyProduct($printtyPrinttyProduct, $printtyProductInserts);
//                    if ($printtyPrinttyProduct->productSizes->count()) {
//                        foreach ($printtyPrinttyProduct->productSizes as $printtyProductSize) {
//                            $this->_generatePrinttyProductSize($printtyProductSize, $printtyProductSizeInserts, $printtyPrinttyProduct->product_code);
//                        }
//                    }
//
//                    if ($printtyPrinttyProduct->productColors->count()) {
//                        foreach ($printtyPrinttyProduct->productColors as $printtyProductColor) {
//                            $this->_generatePrinttyProductColor($printtyProductColor, $printtyProductColorInserts, $printtyPrinttyProduct->product_code);
//
//                            if ($printtyProductColor->productColorSides->count()) {
//                                foreach ($printtyProductColor->productColorSides as $productColorSide) {
//                                    $this->_generatePrinttyProductColorSide($printtyProductColor, $printtyProductColorInserts, $printtyPrinttyProduct->product_code);
//                                }
//                            }
//                        }
//                    }
//                }
//            } else {
//                var_dump('Printty product is not found');
//            }
//        } else {
//            var_dump('No diff');
//        }
//    }

    private function _generatePrinttyProduct($printtyProduct, &$printtyProductInserts)
    {
        $printtyProductInserts[] = [
            'id'                       => $printtyProduct->id,
            'product_code'             => $printtyProduct->product_code,
            'is_for_nekoposu_delivery' => 0,
        ];
    }

    private function _generatePrinttyProductSize($printtyProductSize, &$printtyProductSizeInserts, $productCode)
    {
        $printtyProductSizeInserts[] = [
            'id'           => $printtyProductSize->id,
            'product_code' => $productCode,
            'size_name'    => $printtyProductSize->title,
            'created'      => $printtyProductSize->created_at,
            'modified'     => $printtyProductSize->updated_at,
        ];
    }

    private function _generatePrinttyProductColor($printtyProductColor, &$printtyProductColorInserts, $productCode)
    {
        $printtyProductColorInserts[] = [
            'id'           => $printtyProductColor->id,
            'product_code' => $productCode,
            'size_name'    => $printtyProductColor->title,
            'created'      => $printtyProductColor->created_at,
            'modified'     => $printtyProductColor->updated_at,
        ];
    }

    private function _generatePrinttyProductColorSide($printtyProductColorSide, &$printtyProductColorSideInserts, $productCode)
    {
        $printtyProductColorSideInserts[] = [
            'id'           => $printtyProductColorSide->id,
            'product_code' => $productCode,
            'size_name'    => $printtyProductColorSide->title,
            'created'      => $printtyProductColorSide->created_at,
            'modified'     => $printtyProductColorSide->updated_at,
        ];
    }
}
