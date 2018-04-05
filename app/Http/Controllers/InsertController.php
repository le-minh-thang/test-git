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
                ->pluck('title', 'code')->toArray();
            $products        = Product::select('title', 'code')->pluck('title', 'code')->toArray();
            $diffs           = array_diff_assoc($masterItemTypes, $products);

            $lastProductSizeOrder  = ProductSize::orderBy('order', 'desc')->first()->order + 1;
            $lastProductColorOrder = ProductColor::orderBy('order', 'desc')->first()->order;
            foreach ($diffs as $code => $name) {
                $item = MasterItemType::with('itemSubs', 'itemSizes', 'itemSubs.itemSubSides', 'printtyProduct', 'printtyProductColors', 'printtyProductColorSides', 'printtyProductSizes')
                    ->where('name', $name)
                    ->where('item_code', $code)
                    ->first();

                $productInserts[] = $this->generateProduct($item, $lastProductId);
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

        if ($item->name == '名刺ケース') {
            $price = $toolPrice = 1800;
        } else if ($item->name == 'コインケース' || $item->name == 'ライター') {
            $price = $toolPrice = 1500;
        } else if ($item->name == 'ワイヤレスバッテリー') {
            $price = $toolPrice = 1580;
        } else if ($item->name == 'ファインジャージーTシャツ' || $item->name == 'ファインジャージーTシャツ（ガールズ）') {
            $price     = 1000;
            $toolPrice = 1700;
        } else if ($item->name == 'ドライカノコユーティリティーポロシャツ') {
            $price     = 1400;
            $toolPrice = 2100;
        } else if ($item->name == 'Tシャツワンピース（ミニ丈）') {
            $price     = 1400;
            $toolPrice = 2400;
        } else if ($item->name == 'ヘヴィーウェイトコットンポロシャツ') {
            $price     = 1900;
            $toolPrice = 2750;
        } else if ($item->name == 'オックスフォードボタンダウンショートスリーブシャツ') {
            $price     = 2500;
            $toolPrice = 3200;
        } else {
            $price     = $item->item_price;
            $toolPrice = $item->tool_price;
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
            'price'            => $price,
            // 'price'         => $item->item_price,
            'is_main'          => $item->is_main,
            'is_deleted'       => $delete,
            'order'            => $item->order,
            'created_at'       => $item->created,
            'updated_at'       => $item->modified,
            'tool_price'       => $toolPrice,
            // 'tool_price'    => $item->tool_price,
            'color_total'      => $item->color_total,
            'size'             => $item->size,
            'sale_price'       => $item->sale_price,
            'item_code_nomial' => $item->item_code_nominal,
            'material'         => $item->material,
            'maker'            => $item->maker,
        ];
    }


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

    private function _getPrintPrice($item, $order, $printPrice, $isWhite = false)
    {
        if ($item->name == '名刺ケース' || $item->name == 'コインケース' || $item->name == 'ワイヤレスバッテリー' || $item->name == 'ライター') {
            $printPrice = 0;
        }

        if ($item->name == 'ファインジャージーTシャツ' || $item->name == 'ファインジャージーTシャツ（ガールズ）') {
            if ($isWhite) {
                if ($order == 1) {
                    $printPrice = 700;
                } else if ($order == 2) {
                    $printPrice = 700;
                } else {
                    $printPrice = 450;
                }
            } else {
                if ($order == 1) {
                    $printPrice = 1000;
                } else if ($order == 2) {
                    $printPrice = 1000;
                } else {
                    $printPrice = 650;
                }
            }
        } else if ($item->name == 'ドライカノコユーティリティーポロシャツ') {
            if ($isWhite) {
                if ($order == 1) {
                    $printPrice = 700;
                } else if ($order == 2) {
                    $printPrice = 850;
                } else {
                    $printPrice = 500;
                }
            } else {
                if ($order == 1) {
                    $printPrice = 850;
                } else if ($order == 2) {
                    $printPrice = 1000;
                } else {
                    $printPrice = 700;
                }
            }
        } else if ($item->name == 'Tシャツワンピース（ミニ丈）') {
            if (!$isWhite) {
                if ($order == 1) {
                    $printPrice = 1000;
                } else if ($order == 2) {
                    $printPrice = 1000;
                }
            }
        } else if ($item->name == 'ヘヴィーウェイトコットンポロシャツ') {
            if ($isWhite) {
                if ($order == 1) {
                    $printPrice = 850;
                } else if ($order == 2) {
                    $printPrice = 1000;
                } else {
                    $printPrice = 700;
                }
            } else {
                if ($order == 1) {
                    $printPrice = 700;
                } else if ($order == 2) {
                    $printPrice = 850;
                } else {
                    $printPrice = 450;
                }
            }
        } else if ($item->name == 'オックスフォードボタンダウンショートスリーブシャツ') {
            if ($isWhite) {
                if ($order == 1) {
                    $printPrice = 700;
                } else if ($order == 2) {
                    $printPrice = 850;
                } else {
                    $printPrice = 450;
                }
            } else {
                if ($order == 1) {
                    $printPrice = 950;
                } else if ($order == 2) {
                    $printPrice = 1100;
                } else {
                    $printPrice = 800;
                }
            }
        }

        return $printPrice;
    }
}
