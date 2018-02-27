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

class TestController extends Controller
{
    private $_dateTime;

    public function __construct()
    {
//        $product = Product::orderBy('id', 'desc')->first();
//
//        $dateOrder = \Carbon\Carbon::parse($product->updated_at);
//        $now       = \Carbon\Carbon::now();
//        print_r($now);
//        dd($product->updated_at);
//        $flag      = false;
//        if ($dateOrder->diffInDays($now) <= 180) {
//            $flag = true;
//        }
//        dd($flag);
//        $dateOrder = Carbon::now()->subMonth();
//        $now       = Carbon::now();
//
//        dd($dateOrder->diffInDays($now));
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

            $masterItemTypes = MasterItemType::select('name as title', 'item_code as code')->pluck('title', 'code')->toArray();
            $products        = Product::select('title', 'code')->pluck('title', 'code')->toArray();
            $diffs           = array_diff_assoc($masterItemTypes, $products);

            var_dump("generate a product...");
            foreach ($diffs as $code => $name) {
                $item             = MasterItemType::with('itemSubs', 'itemSubs.itemSubSizes', 'printtyProduct', 'printtyProductColors', 'printtyProductColorSides', 'printtyProductSizes')->where('name', $name)->where('item_code', $code)->first();
                $productInserts[] = $this->generateProduct($item, $lastProductId);
                var_dump("generate a product $lastProductId");
                $productSizeInserts = $this->generateProductSizes($productSizeInserts, $lastProductId);

                if (!emtpy($item->printtyProduct)) {
                    $printtyProductInserts[] = $this->_generatePrinttyProduct($item->printtyProduct);
                }

                if (!emtpy($item->printtyProductSizes)) {
                    $this->_generatePrinttyProductSizes($item->printtyProductSizes, $printtyProductSizeInserts);
                }

                if (!emtpy($item->printtyProductColors)) {
                    $this->_generatePrinttyProductColors($item->printtyProductColors, $printtyProductColorInserts);
                }

                if (!emtpy($item->printtyProductColorSides)) {
                    $this->_generatePrinttyProductColorSides($item->printtyProductColorSides, $printtyProductColorSideInserts);
                }

                foreach ($item->itemSubs as $itemSub) {
                    $productColorInserts[] = $this->generateProductColors($itemSub, $lastProductId, $lastProductColorId);
                    foreach ($itemSub->itemSubSizes as $itemSubSize) {
                        $productColorSideInserts[] = $this->generateProductColorSides($itemSubSize, $lastProductColorId, $itemSub);
                    }

                    $lastProductColorId += 1;
                }

                $lastProductId += 1;
            }

            var_dump("$printtyProductInserts");
            var_dump($printtyProductInserts);
            var_dump("$printtyProductSizeInserts");
            var_dump($printtyProductSizeInserts);
            var_dump("$printtyProductSizeInserts");
            var_dump($printtyProductSizeInserts);
            var_dump("$printtyProductColorInserts");
            var_dump($printtyProductColorInserts);
            var_dump("$printtyProductColorSideInserts");
            var_dump($printtyProductColorSideInserts);

            DB::beginTransaction();
            var_dump("inserting ...");
            Product::insert($productInserts);
            ProductSize::insert($productSizeInserts);
            ProductColor::insert($productColorInserts);
            ProductColorSide::insert($productColorSideInserts);
            //
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

    private function generateProductColorSides($itemSubSize, $lastProductColorId, $itemSub)
    {
        $order = null;
        if ($itemSubSize->title == '表' || $itemSubSize->title == '表裏同じ') {
            $print_price = $itemSub->cost1;
            $order       = 1;
        } else if ($itemSubSize->title == '裏') {
            $print_price = $itemSub->cost2;
            $order       = 2;
        } else {
            $print_price = $itemSub->cost3;
            if ($itemSubSize->title == '左袖') {
                $order = 3;
            } else if ($itemSubSize->title == '右袖') {
                $order = 4;
            }
        }
        return [
            'title'            => $itemSubSize->title,
            'product_color_id' => $lastProductColorId,
            'is_main'          => $itemSubSize->is_main,
            'content'          => $itemSubSize->content,
            'image_url'        => $itemSubSize->image_url,
            'preview_url'      => $itemSubSize->preview_url,
            'print_price'      => $print_price,
            'is_deleted'       => 0,
            'order'            => $order,
            'created_at'       => $itemSubSize->created,
            'updated_at'       => $itemSubSize->modified,
            'content_print'    => $itemSubSize->content_print,
        ];
    }


    private function generateProductColors($itemSub, $lastProductId, $lastProductColorId)
    {
        return [
            'id'         => $lastProductColorId,
            'title'      => $itemSub->name,
            'value'      => $itemSub->color,
            'code'       => $itemSub->item_code,
            'product_id' => $lastProductId,
            'is_main'    => $itemSub->is_main,
            'is_deleted' => 0,
            'created_at' => $itemSub->created,
            'updated_at' => $itemSub->modified,
        ];
    }

    private function generateProduct($item, $lastProductId)
    {
        return [
            'id'               => $lastProductId,
            'category_id'      => $item->category_id,
            'title'            => $item->name,
            'code'             => $item->item_code,
            'price'            => $item->item_price,
            'is_main'          => $item->is_main,
            'is_deleted'       => 0,
            'order'            => $item->order,
            'created_at'       => $item->created,
            'updated_at'       => $item->modified,
            'tool_price'       => $item->tool_price,
            'color_total'      => $item->color_total,
            'size'             => $item->size,
            'sale_price'       => $item->sale_price,
            'item_code_nomial' => $item->item_code_nomial,
            'material'         => $item->material,
            'maker'            => $item->maker,
        ];
    }

    private function generateProductSizes($productSizeInserts, $lastProductId)
    {
        $sizes = [
            '100',
            '110',
            '120',
            '130',
            '140',
            '150',
            '160',
            'WS',
            'WM',
            'WL',
            'S',
            'M',
            'L',
            'XL',
            'XXL',
            'XXXL',
        ];

        $isFirst = true;
        foreach ($sizes as $size) {
            $productSizeInserts[] = [
                'product_id' => $lastProductId,
                'title'      => $size,
                'is_main'    => $isFirst ? 1 : 0,
                'is_deleted' => 0,
                'created_at' => $this->_dateTime,
                'updated_at' => $this->_dateTime,
            ];
            $isFirst              = false;
        }

        return $productSizeInserts;
    }
}
