<?php

namespace App\Http\Controllers;

use App\PrinttyProduct;
use App\PrinttyProductColor;
use App\PrinttyProductColorSide;
use App\PrinttyProductSize;
use App\ProductSize;
use DB;
use App\Product;
use App\ProductColor;
use App\MasterItemType;
use App\ProductColorSide;
use Carbon\Carbon;

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

            $lastProductId      = Product::orderBy('id', 'desc')->first()->id + 1;
            $lastProductColorId = ProductColor::orderBy('id', 'desc')->first()->id + 1;

            $masterItemTypes = MasterItemType::select('name as title', 'item_code as code')
//                ->whereNotIn('name', ['スマホリング（ハート型）', 'レザーキーホルダー（丸型）', 'レザーキーホルダー（四角型）', 'レザーキーホルダー（Tシャツ型）'])
                ->pluck('title', 'code')->toArray();
            $products        = Product::select('title', 'code')->pluck('title', 'code')->toArray();
            $diffs           = array_diff_assoc($masterItemTypes, $products);

            foreach ($diffs as $code => $name) {
                $item = MasterItemType::with('itemSubs', 'itemSizes', 'itemSubs.itemSubSides', 'printtyProduct', 'printtyProductColors', 'printtyProductColorSides', 'printtyProductSizes')
                    ->where('name', $name)
                    ->where('item_code', $code)
                    ->first();

                $productInserts[] = $this->generateProduct($item, $lastProductId);
                var_dump("generate the product $lastProductId");
                $this->generateProductSizes($productSizeInserts, $lastProductId, $item->itemSizes);

//                if (!emtpy($item->printtyProduct)) {
//                    $printtyProductInserts[] = $this->_generatePrinttyProduct($item->printtyProduct);
//                }

//                if (!emtpy($item->printtyProductSizes)) {
//                    $this->_generatePrinttyProductSizes($item->printtyProductSizes, $printtyProductSizeInserts);
//                }

//                if (!emtpy($item->printtyProductColors)) {
//                    $this->_generatePrinttyProductColors($item->printtyProductColors, $printtyProductColorInserts);
//                }

//                if (!emtpy($item->printtyProductColorSides)) {
//                    $this->_generatePrinttyProductColorSides($item->printtyProductColorSides, $printtyProductColorSideInserts);
//                }

                foreach ($item->itemSubs as $itemSub) {
                    $productColorInserts[] = $this->generateProductColors($itemSub, $lastProductId, $lastProductColorId);
                    foreach ($itemSub->itemSubSides as $itemSubSide) {
                        $productColorSideInserts[] = $this->generateProductColorSides($itemSubSide, $lastProductColorId, $itemSub);
                    }

                    $lastProductColorId += 1;
                }

                $lastProductId += 1;
            }

            DB::beginTransaction();
            var_dump("inserting ...");
            Product::insert($productInserts);
            ProductSize::insert($productSizeInserts);
            ProductColor::insert($productColorInserts);
            ProductColorSide::insert($productColorSideInserts);
            //
//            PrinttyProduct::insert($printtyProductInserts);
//            PrinttyProductSize::insert($printtyProductSizeInserts);
//            PrinttyProductColor::insert($printtyProductColorInserts);
//            PrinttyProductColorSide::insert($printtyProductColorSideInserts);

            DB::commit();
            dd('done');
        } catch (\Exception $exception) {
            DB::rollBack();
            var_dump("something went wrong");
            dd($exception);
        }
    }


    private function _generatePrinttyProductSizes($printtyProductSizes, &$printtyProductSizeInserts)
    {
        foreach ($printtyProductSizes as $printtyProductSize) {
            $printtyProductSizeInserts[] = [
                'id'           => $printtyProductSize->id,
                'product_code' => $printtyProductSize->product_code,
                'size_name'    => $printtyProductSize->size_name,
                'modified'     => $printtyProductSize->modified,
                'created'      => $printtyProductSize->created,
            ];
        }
    }

    private function _generatePrinttyProductColorSides($printtyProductColorSides, &$printtyProductColorInserts)
    {
        foreach ($printtyProductColorSides as $printtyProductColorSide) {
            $printtyProductColorInserts[] = [
                'id'                  => $printtyProductColorSide->id,
                'product_code'        => $printtyProductColorSide->product_code,
                'products_color_code' => $printtyProductColorSide->products_color_code,
                'side_name'           => $printtyProductColorSide->side_name,
                'print_width'         => $printtyProductColorSide->print_width,
                'print_height'        => $printtyProductColorSide->print_height,
                'modified'            => $printtyProductColorSide->modified,
                'created'             => $printtyProductColorSide->created,
            ];
        }
    }

    private function _generatePrinttyProductColors($printtyProductColors, &$printtyProductColorInserts)
    {
        foreach ($printtyProductColors as $printtyProductColor) {
            $printtyProductColorInserts[] = [
                'id'                  => $printtyProductColor->id,
                'product_code'        => $printtyProductColor->product_code,
                'products_color_code' => $printtyProductColor->products_color_code,
                'modified'            => $printtyProductColor->modified,
                'created'             => $printtyProductColor->created,
            ];
        }
    }

    private function _generatePrinttyProduct($printtyProduct)
    {
        return [
            'id'                       => $printtyProduct->id,
            'product_code'             => $printtyProduct->product_code,
            'is_for_nekoposu_delivery' => $printtyProduct->is_for_nekoposu_delivery,
            'nekopos_quantity_count'   => $printtyProduct->nekopos_quantity_count,
            'nekopos_weight'           => $printtyProduct->nekopos_weight,
            'modified'                 => $printtyProduct->modified,
            'created'                  => $printtyProduct->created,
        ];
    }

    private function generateProductColorSides($itemSubSide, $lastProductColorId, $itemSub)
    {
        $order = null;
        if ($itemSub->color == '#ffffff' || $itemSub->color == '#FFFFFF') {
            if ($itemSubSide->title == '表' || $itemSubSide->title == '表裏同じ' || $itemSubSide->title == '左前' || $itemSubSide->title == '右前') {
//                $print_price = 0;
                $print_price = $itemSub->cost1;
                $order       = 1;
            } else if ($itemSubSide->title == '裏' || $itemSubSide->title == '左後' || $itemSubSide->title == '右後') {
                $print_price = $itemSub->cost2;
                $order       = 2;
            } else {
                $print_price = $itemSub->cost3;
                if ($itemSubSide->title == '左袖') {
                    $order = 3;
                } else if ($itemSubSide->title == '右袖') {
                    $order = 4;
                }
            }
        } else {
            if ($itemSubSide->title == '表' || $itemSubSide->title == '表裏同じ' || $itemSubSide->title == '左前' || $itemSubSide->title == '右前') {
//                $print_price = 0;
                $print_price = $itemSub->cost1;
                $order       = 1;
            } else if ($itemSubSide->title == '裏' || $itemSubSide->title == '左後' || $itemSubSide->title == '右後') {
                $print_price = $itemSub->cost2;
                $order       = 2;
            } else {
                $print_price = $itemSub->cost3;
                if ($itemSubSide->title == '左袖') {
                    $order = 3;
                } else if ($itemSubSide->title == '右袖') {
                    $order = 4;
                }
            }
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
            'print_price'      => $print_price,
            'is_deleted'       => $delete,
            'order'            => $order,
            'created_at'       => $itemSubSide->created,
            'updated_at'       => $itemSubSide->modified,
            'content_print'    => $itemSubSide->content_print,
        ];
    }


    private function generateProductColors($itemSub, $lastProductId, $lastProductColorId)
    {
        if ($itemSub->state == 1) {
            $delete = 0;
        } else {
            $delete = 1;
        }
        return [
            'id'         => $lastProductColorId,
            'title'      => $itemSub->name,
            'value'      => $itemSub->color,
            'code'       => $itemSub->item_code,
            'product_id' => $lastProductId,
            'is_main'    => $itemSub->is_main,
            'is_deleted' => $delete,
            'created_at' => $itemSub->created,
            'updated_at' => $itemSub->modified,
        ];
    }

    private function generateProduct($item, $lastProductId)
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

        // delete
//        if ($item->name == 'スマホリング（ハート型）') {
//            $price = $toolPrice = 750;
//        } else {
//            $price = $toolPrice = 600;
//        }

        return [
            'id'               => $lastProductId,
            'category_id'      => $category,
            'title'            => $item->name,
            'code'             => $item->item_code,
            //            'price'            => $price,
            'price'            => $item->item_price,
            'is_main'          => $item->is_main,
            'is_deleted'       => $delete,
            'order'            => $item->order,
            'created_at'       => $item->created,
            'updated_at'       => $item->modified,
            //            'tool_price'       => $toolPrice,
            'tool_price'       => $item->tool_price,
            'color_total'      => $item->color_total,
            'size'             => $item->size,
            'sale_price'       => $item->sale_price,
            'item_code_nomial' => $item->item_code_nominal,
            'material'         => $item->material,
            'maker'            => $item->maker,
        ];
    }


    private function generateProductSizes(&$productSizeInserts, $lastProductId, $itemSizes)
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
                'created_at' => $itemSize->created,
                'updated_at' => $itemSize->modified,
            ];
        }
    }
}
