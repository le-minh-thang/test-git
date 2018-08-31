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
    private $_productPrices      = [];
    private $_printProductPrices = [];

    public function __construct()
    {
        $this->_setProductPrices();
    }

    /**
     * Display a listing of new items.
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
                ->where('category_id', '>', 0)
                ->where('id', '<>', 'IT489')
                ->pluck('title', 'code')
                ->toArray();

            $products = Product::select('title', 'code')->pluck('title', 'code')->toArray();
            $diffs    = array_diff_assoc($masterItemTypes, $products);

            // Unless self insult, please dump and die $diffs before add items
            // dd($diffs);

            $lastProductSizeOrder  = ProductSize::orderBy('order', 'desc')->first()->order + 1;
            $lastProductColorOrder = ProductColor::orderBy('order', 'desc')->first()->order;
            foreach ($diffs as $code => $name) {
                $item = MasterItemType::with('itemSubs', 'itemSizes', 'itemSubs.itemSubSides', 'printtyProduct', 'printtyProductColors', 'printtyProductColorSides', 'printtyProductSizes')
                    ->where('name', $name)
                    ->where('item_code', $code)
                    ->first();

                $productInserts[] = $this->_generateProduct($item, $lastProductId);
                var_dump("generate the product $lastProductId");
                $this->_generateProductSizes($productSizeInserts, $lastProductId, $item->itemSizes, $lastProductSizeOrder);

                foreach ($item->itemSubs as $itemSub) {
                    $productColorInserts[] = $this->_generateProductColors($itemSub, $lastProductId, $lastProductColorId, $lastProductColorOrder);
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

    /**
     * Prepare product color side data
     *
     * @param $itemSubSide
     * @param $lastProductColorId
     * @param $itemSub
     * @param $item
     * @return array
     */
    private function generateProductColorSides($itemSubSide, $lastProductColorId, $itemSub, $item)
    {
        $order = null;
        if ($itemSubSide->side_name == 'front') {
            $order = 1;
        } else {
            if ((int)$itemSubSide->side_name <= 4) {
                $order = (int)$itemSubSide->side_name;
            } else {
                if ($itemSubSide->title == '表' || $itemSubSide->title == '表裏同じ' || $itemSubSide->title == '左前' || $itemSubSide->title == '右前') {
                    $order = 1;
                } else if ($itemSubSide->title == '裏' || $itemSubSide->title == '左後' || $itemSubSide->title == '右後') {
                    $order = 2;
                } else {
                    if ($itemSubSide->title == '左袖') {
                        $order = 3;
                    } else if ($itemSubSide->title == '右袖') {
                        $order = 4;
                    }
                }
            }
        }

        if ($order == 1) {
            $printPriceCost = $itemSub->cost1;
        } else if ($order == 2) {
            $printPriceCost = $itemSub->cost2;
        } else if ($order == 3 || $order == 4) {
            $printPriceCost = $itemSub->cost3;
        } else {
            $printPriceCost = 0; // This line can prevent IDE mention the variable might has not been defined
            dd('Something went wrong!');
        }

        $color = 'others';

        // White color depend on the white colors were defined on the excel sheet
        if ($itemSub->color == '#ffffff' || $itemSub->color == '#FFFFFF' || $itemSub->color == '#edeef0' || $itemSub->color == '#edeef2' || $itemSub->color == '#dfdee3' || $itemSub->color == '#eceff2') {
            $color = 'white';
        }

        // Get print price for the product
        $printPriceCost = $this->_getPrintPrice($item, $order, $printPriceCost, $color);

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

    /**
     * Prepare product colors
     *
     * @param $itemSub
     * @param $lastProductId
     * @param $lastProductColorId
     * @param $lastProductColorOrder
     * @return array
     */
    private function _generateProductColors($itemSub, $lastProductId, $lastProductColorId, &$lastProductColorOrder)
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

    /**
     * Prepare product data
     *
     * @param $item
     * @param $lastProductId
     * @return array
     */
    private function _generateProduct($item, $lastProductId)
    {
        $noboriCategories = [
            24 => 24,
            25 => 25,
            26 => 26,
            27 => 27,
            28 => 28,
            48 => 48,
            49 => 49,
            51 => 51,
        ];

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
            $categoryId = 24;
        } else if ($item->category_id == 23) {
            $categoryId = 25;
        } else if ($item->category_id == 24) {
            $categoryId = 26;
        } else if ($item->category_id == 25) {
            $categoryId = 27;
        } else if ($item->category_id == 26) {
            $categoryId = 28;
        } else if ($item->category_id == 27) {
            $categoryId = 22;
        } else if ($item->category_id == 28) {
            $categoryId = 23;
        } else {
            $categoryId = $item->category_id;
        }

        $prices    = $this->_getProductPrices($item);
        $price     = $prices['price'];
        $toolPrice = $prices['tool_price'];

        return [
            'id'               => $lastProductId,
            'category_id'      => $categoryId,
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
            'is_nobori'        => isset($noboriCategories[$categoryId]) ? 1 : 0,
            'sale_price'       => $item->sale_price,
            'item_code_nomial' => $item->item_code_nominal,
            'material'         => $item->material,
            'maker'            => $item->maker,
        ];
    }

    /**
     * Get product price and product print price
     *
     * @param $item
     * @return array
     */
    private function _getProductPrices($item)
    {
        if (isset($this->_productPrices[$item->id])) {
            if (isset($this->_productPrices[$item->id]['price'])) {
                $price = $this->_productPrices[$item->id]['price'];
            } else {
                $price = $item->item_price;
            }

            if (isset($this->_productPrices[$item->id]['tool_price'])) {
                $toolPrice = $this->_productPrices[$item->id]['tool_price'];
            } else {
                $toolPrice = $item->tool_price;
            }
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
    private function _generateProductSizes(&$productSizeInserts, $lastProductId, $itemSizes, &$lastProductSizeOrder)
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
     * @param $color
     * @return int
     */
    private function _getPrintPrice($item, $order, $printPrice, $color)
    {
        $position = $order;
        if ($order == 4) {
            $position = 3;
        }

        if (isset($this->_printProductPrices[$color][$item->id][$position])) {
            $printPrice = $this->_printProductPrices[$color][$item->id][$position];
        }

        return $printPrice;
    }

    /**
     * Check active link
     */
    public function checkLink()
    {
        $first   = 'http://';
        $content = '';
        $third   = '.mp3';
        for ($i = 0; $i <= strlen($content); $i++) {
            $url = sprintf('%s%s%s%s%s', $first, (substr($content, 0, $i)), strtoupper(substr($content, $i, 1)), (substr($content, $i + 1)), $third);
            var_dump($url);
            $returned_content = $this->_getData($url);
            if (preg_match('/(404 Not Found)/', $returned_content)) {
//                var_dump('not match');
            } else {
                dd('match');
            }
        }
    }

    /**
     * Get data
     *
     * @param $url
     * @return mixed
     */
    private function _getData($url)
    {
        $ch      = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * Set product and print product prices
     */
    private function _setProductPrices()
    {
        // Everything is different from add items file

        $this->_productPrices = [
            'IT476'    => [
                'price'      => 600,
                'tool_price' => 1300,
            ],
            'IT477'    => [
                'price'      => 600,
                'tool_price' => 1300,
            ],
            'IT478'    => [
                'price'      => 1000,
                'tool_price' => 1900,
            ],
            'IT482'    => [
                'price'      => 1300,
                'tool_price' => 2100,
            ], 'IT483' => [
                'price'      => 1700,
                'tool_price' => 2500,
            ], 'IT488' => [
                'price'      => 700,
                'tool_price' => 1500,
            ], 'IT489' => [
                'price'      => 1800,
                'tool_price' => 2600,
            ], 'IT490' => [
                'price'      => 600,
                'tool_price' => 1400,
            ], 'IT491' => [
                'price'      => 1500,
                'tool_price' => 2300,
            ], 'IT493' => [
                'price' => 1300,
            ],
        ];

        $this->_productPrices['IT479'] = $this->_productPrices['IT478'];
        $this->_productPrices['IT480'] = $this->_productPrices['IT478'];
        $this->_productPrices['IT481'] = $this->_productPrices['IT478'];
        $this->_productPrices['IT484'] = $this->_productPrices['IT482'];
        $this->_productPrices['IT485'] = $this->_productPrices['IT482'];
        $this->_productPrices['IT492'] = $this->_productPrices['IT482'];
        $this->_productPrices['IT495'] = $this->_productPrices['IT482'];
        $this->_productPrices['IT486'] = $this->_productPrices['IT483'];
        $this->_productPrices['IT487'] = $this->_productPrices['IT483'];
        $this->_productPrices['IT494'] = $this->_productPrices['IT483'];

        $this->_printProductPrices = [
            'white'  => [
                'IT482'    => [
                    1 => 800,
                    2 => 800,
                    3 => 800,
                ], 'IT483' => [
                    1 => 800,
                    2 => 800,
                ], 'IT488' => [
                    1 => 800,
                ],
            ],
            'others' => [
                'IT476'    => [
                    1 => 700,
                    2 => 700,
                ], 'IT478' => [
                    1 => 900,
                    2 => 900,
                ], 'IT482' => [
                    1 => 1000,
                    2 => 1000,
                    3 => 1000,
                ], 'IT491' => [
                    1 => 1300,
                    2 => 1300,
                    3 => 1000,
                ], 'IT483' => [
                    1 => 1000,
                    2 => 1000,
                ], 'IT488' => [
                    1 => 1000,
                ],
            ],
        ];

        // White
        $this->_printProductPrices['white']['IT484'] = $this->_printProductPrices['white']['IT482'];
        $this->_printProductPrices['white']['IT485'] = $this->_printProductPrices['white']['IT482'];
        $this->_printProductPrices['white']['IT491'] = $this->_printProductPrices['white']['IT482'];
        $this->_printProductPrices['white']['IT492'] = $this->_printProductPrices['white']['IT482'];
        $this->_printProductPrices['white']['IT495'] = $this->_printProductPrices['white']['IT482'];
        $this->_printProductPrices['white']['IT486'] = $this->_printProductPrices['white']['IT483'];
        $this->_printProductPrices['white']['IT487'] = $this->_printProductPrices['white']['IT483'];
        $this->_printProductPrices['white']['IT493'] = $this->_printProductPrices['white']['IT483'];
        $this->_printProductPrices['white']['IT494'] = $this->_printProductPrices['white']['IT483'];
        $this->_printProductPrices['white']['IT489'] = $this->_printProductPrices['white']['IT488'];
        $this->_printProductPrices['white']['IT490'] = $this->_printProductPrices['white']['IT488'];

        // The other colors
        $this->_printProductPrices['others']['IT477'] = $this->_printProductPrices['others']['IT476'];
        $this->_printProductPrices['others']['IT479'] = $this->_printProductPrices['others']['IT478'];
        $this->_printProductPrices['others']['IT480'] = $this->_printProductPrices['others']['IT478'];
        $this->_printProductPrices['others']['IT481'] = $this->_printProductPrices['others']['IT478'];
        $this->_printProductPrices['others']['IT484'] = $this->_printProductPrices['others']['IT482'];
        $this->_printProductPrices['others']['IT485'] = $this->_printProductPrices['others']['IT482'];
        $this->_printProductPrices['others']['IT492'] = $this->_printProductPrices['others']['IT482'];
        $this->_printProductPrices['others']['IT495'] = $this->_printProductPrices['others']['IT482'];
        $this->_printProductPrices['others']['IT486'] = $this->_printProductPrices['others']['IT483'];
        $this->_printProductPrices['others']['IT487'] = $this->_printProductPrices['others']['IT483'];
        $this->_printProductPrices['others']['IT493'] = $this->_printProductPrices['others']['IT483'];
        $this->_printProductPrices['others']['IT494'] = $this->_printProductPrices['others']['IT483'];
        $this->_printProductPrices['others']['IT489'] = $this->_printProductPrices['others']['IT488'];
        $this->_printProductPrices['others']['IT490'] = $this->_printProductPrices['others']['IT488'];
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
    //
    //    private function _generatePrinttyProduct($printtyProduct, &$printtyProductInserts)
    //    {
    //        $printtyProductInserts[] = [
    //            'id'                       => $printtyProduct->id,
    //            'product_code'             => $printtyProduct->product_code,
    //            'is_for_nekoposu_delivery' => 0,
    //        ];
    //    }
    //
    //    private function _generatePrinttyProductSize($printtyProductSize, &$printtyProductSizeInserts, $productCode)
    //    {
    //        $printtyProductSizeInserts[] = [
    //            'id'           => $printtyProductSize->id,
    //            'product_code' => $productCode,
    //            'size_name'    => $printtyProductSize->title,
    //            'created'      => $printtyProductSize->created_at,
    //            'modified'     => $printtyProductSize->updated_at,
    //        ];
    //    }
    //
    //    private function _generatePrinttyProductColor($printtyProductColor, &$printtyProductColorInserts, $productCode)
    //    {
    //        $printtyProductColorInserts[] = [
    //            'id'           => $printtyProductColor->id,
    //            'product_code' => $productCode,
    //            'size_name'    => $printtyProductColor->title,
    //            'created'      => $printtyProductColor->created_at,
    //            'modified'     => $printtyProductColor->updated_at,
    //        ];
    //    }
    //
    //    private function _generatePrinttyProductColorSide($printtyProductColorSide, &$printtyProductColorSideInserts, $productCode)
    //    {
    //        $printtyProductColorSideInserts[] = [
    //            'id'           => $printtyProductColorSide->id,
    //            'product_code' => $productCode,
    //            'size_name'    => $printtyProductColorSide->title,
    //            'created'      => $printtyProductColorSide->created_at,
    //            'modified'     => $printtyProductColorSide->updated_at,
    //        ];
    //    }
}
