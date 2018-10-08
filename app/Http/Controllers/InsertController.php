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
    private $_productIds         = [];
    private $_productPrices      = [];
    private $_printProductPrices = [];

    public function __construct()
    {
        parent::__construct();

        $this->_setProductPrices();
    }

    /**
     * Display a listing of new items.
     *
     * @param $test
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $productInserts                 = [];
            $productSizeInserts             = [];
            $productColorInserts            = [];
            $printtyProductInserts          = [];
            $productColorSideInserts        = [];
            $printtyProductSizeInserts      = [];
            $printtyProductColorInserts     = [];
            $printtyProductColorSideInserts = [];

            $printtyProducts = [
                'PrinttyProduct'          => 'UpTPrinttyProduct',
                'PrinttyProductSize'      => 'UpTPrinttyProductSize',
                'PrinttyProductColor'     => 'UpTPrinttyProductColor',
                'PrinttyProductColorSide' => 'UpTPrinttyProductColorSide',
            ];

            // $lastProductId      = Product::orderBy('id', 'desc')->first()->id + 1; (1)
            $lastProductColorId = ProductColor::orderBy('id', 'desc')->first()->id + 1;

            $masterItemTypes = MasterItemType::select('name as title', 'item_code as code')
                ->where('category_id', '>', 0)
//                ->where('id', '<>', 'IT489')
                ->where('id', '>', 'IT524')
                ->pluck('title', 'code')
                ->toArray();

            $products = Product::select('title', 'code')->pluck('title', 'code')->toArray();
            $diffs    = array_diff_assoc($masterItemTypes, $products);

            // Unless self insult, please dump and die $diffs before add items
            // dd($diffs);

            $lastProductSizeOrder  = ProductSize::orderBy('order', 'desc')->first()->order + 1;
            $lastProductColorOrder = ProductColor::orderBy('order', 'desc')->first()->order;
            foreach ($diffs as $code => $name) {
                $item = MasterItemType::with('itemSizes', 'itemSubs.itemSubSides')
                    ->where('name', $name)
                    ->where('item_code', $code)
                    ->first();

                // check product id before insert into DB
                $this->_setProductId($item->id);

                if (!isset($this->_productIds[$item->id])) {
                    dd('Look like something went wrong!');
                }

                $lastProductId    = $this->_productIds[$item->id];
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

                // use this when automatically generate $lastProductId base on DB, it was commented at (1)
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

            var_dump($productInserts);

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
        if ($itemSub->color == '#ffffff' || $itemSub->color == '#FFFFFF' || $itemSub->color == '#edeef0' ||
            $itemSub->color == '#edeef2' || $itemSub->color == '#dfdee3' || $itemSub->color == '#eceff2' ||
            $itemSub->color == '#EAEBEF' || $itemSub->color == '#EBEAF0' || $itemSub->color == '#F6F6F6' ||
            $itemSub->color == '#F3F2F0' || $itemSub->color == '#F3F3F3' || $itemSub->color == '#EFEFF1' ||
            $itemSub->color == '#ecece4'
        ) {
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

        $prices    = $this->_getProductPrices($lastProductId, $item);
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
     * @param $productId
     * @param $item
     * @return array
     */
    private function _getProductPrices($productId, $item)
    {
        if (isset($this->_productPrices[$productId])) {
            if (isset($this->_productPrices[$productId]['price'])) {
                $price = $this->_productPrices[$productId]['price'];
            } else {
                $price = $item->item_price;
            }

            if (isset($this->_productPrices[$productId]['tool_price'])) {
                $toolPrice = $this->_productPrices[$productId]['tool_price'];
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

        if (isset($this->_printProductPrices[$color][$this->_productIds[$item->id]][$position])) {
            $printPrice = $this->_printProductPrices[$color][$this->_productIds[$item->id]][$position];
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
     * Everything is different from add items file
     */
    private function _setProductPrices()
    {
        $this->_productPrices = [
            '409'    => [
                'price'      => 900,
                'tool_price' => 1500,
            ], '410' => [
                'price'      => 1000,
                'tool_price' => 1600,
            ], '411' => [
                'price'      => 900,
                'tool_price' => 1900,
            ], '412' => [
                'price'      => 2000,
                'tool_price' => 2600,
            ], '414' => [
                'price'      => 1600,
                'tool_price' => 2200,
            ], '415' => [
                'price'      => 1100,
                'tool_price' => 1700,
            ], '416' => [
                'price'      => 1200,
                'tool_price' => 1800,
            ], '417' => [
                'price'      => 1700,
                'tool_price' => 2300,
            ], '419' => [
                'price'      => 1900,
                'tool_price' => 2500,
            ], '420' => [
                'price'      => 1300,
                'tool_price' => 1900,
            ], '425' => [
                'price'      => 1800,
                'tool_price' => 3200,
            ],
        ];

        $this->_productPrices['413'] = $this->_productPrices['412'];
        $this->_productPrices['418'] = $this->_productPrices['412'];
        $this->_productPrices['421'] = $this->_productPrices['410'];
        $this->_productPrices['422'] = $this->_productPrices['410'];
        $this->_productPrices['423'] = $this->_productPrices['416'];
        $this->_productPrices['424'] = $this->_productPrices['409'];
        $this->_productPrices['426'] = $this->_productPrices['414'];
        $this->_productPrices['427'] = $this->_productPrices['417'];
        $this->_productPrices['428'] = $this->_productPrices['415'];
        $this->_productPrices['429'] = $this->_productPrices['409'];

        $this->_printProductPrices = [
            'white'  => [
                '409'    => [
                    1 => 600,
                    2 => 600,
                    3 => 400,
                ]
            ],
            'others' => [
                '409'    => [
                    1 => 900,
                    2 => 900,
                    3 => 700,
                ], '421' => [
                    1 => 1400,
                    2 => 1400,
                    3 => 1000,
                ]
            ],
        ];

        // White
        $this->_printProductPrices['white']['410'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['white']['411'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['white']['417'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['white']['418'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['white']['419'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['white']['412'] = $this->_printProductPrices['white']['409'];
        $this->_printProductPrices['white']['413'] = $this->_printProductPrices['white']['409'];
        $this->_printProductPrices['white']['414'] = $this->_printProductPrices['white']['409'];
        $this->_printProductPrices['white']['415'] = $this->_printProductPrices['white']['409'];
        $this->_printProductPrices['white']['416'] = $this->_printProductPrices['white']['409'];
        $this->_printProductPrices['white']['420'] = $this->_printProductPrices['white']['409'];
        $this->_printProductPrices['white']['421'] = $this->_printProductPrices['white']['409'];
        $this->_printProductPrices['white']['422'] = $this->_printProductPrices['white']['409'];
        $this->_printProductPrices['white']['423'] = $this->_printProductPrices['white']['409'];
        $this->_printProductPrices['white']['424'] = $this->_printProductPrices['white']['409'];
        $this->_printProductPrices['white']['426'] = $this->_printProductPrices['white']['409'];
        $this->_printProductPrices['white']['427'] = $this->_printProductPrices['white']['409'];
        $this->_printProductPrices['white']['428'] = $this->_printProductPrices['white']['409'];
        $this->_printProductPrices['white']['429'] = $this->_printProductPrices['white']['409'];
        $this->_printProductPrices['white']['425'] = $this->_printProductPrices['others']['421'];

        // The other colors
        $this->_printProductPrices['others']['410'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['others']['411'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['others']['412'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['others']['413'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['others']['414'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['others']['415'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['others']['416'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['others']['417'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['others']['418'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['others']['419'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['others']['420'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['others']['426'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['others']['427'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['others']['429'] = $this->_printProductPrices['others']['409'];
        $this->_printProductPrices['others']['422'] = $this->_printProductPrices['others']['421'];
        $this->_printProductPrices['others']['423'] = $this->_printProductPrices['others']['421'];
        $this->_printProductPrices['others']['424'] = $this->_printProductPrices['others']['421'];
        $this->_printProductPrices['others']['425'] = $this->_printProductPrices['others']['421'];
        $this->_printProductPrices['others']['428'] = $this->_printProductPrices['others']['421'];
    }

    /**
     * Get product id
     *
     * @param $itemId
     */
    private function _setProductId($itemId)
    {
        if ($itemId == 'IT525') {
            $this->_productIds[$itemId] = 409;
        } else if ($itemId == 'IT526') {
            $this->_productIds[$itemId] = 410;
        } else if ($itemId == 'IT527') {
            $this->_productIds[$itemId] = 411;
        } else if ($itemId == 'IT528') {
            $this->_productIds[$itemId] = 412;
        } else if ($itemId == 'IT529') {
            $this->_productIds[$itemId] = 413;
        } else if ($itemId == 'IT530') {
            $this->_productIds[$itemId] = 414;
        } else if ($itemId == 'IT531') {
            $this->_productIds[$itemId] = 415;
        } else if ($itemId == 'IT532') {
            $this->_productIds[$itemId] = 416;
        } else if ($itemId == 'IT533') {
            $this->_productIds[$itemId] = 417;
        } else if ($itemId == 'IT534') {
            $this->_productIds[$itemId] = 418;
        } else if ($itemId == 'IT535') {
            $this->_productIds[$itemId] = 419;
        } else if ($itemId == 'IT536') {
            $this->_productIds[$itemId] = 420;
        } else if ($itemId == 'IT537') {
            $this->_productIds[$itemId] = 421;
        } else if ($itemId == 'IT538') {
            $this->_productIds[$itemId] = 422;
        } else if ($itemId == 'IT539') {
            $this->_productIds[$itemId] = 423;
        } else if ($itemId == 'IT540') {
            $this->_productIds[$itemId] = 424;
        } else if ($itemId == 'IT541') {
            $this->_productIds[$itemId] = 425;
        } else if ($itemId == 'IT542') {
            $this->_productIds[$itemId] = 426;
        } else if ($itemId == 'IT543') {
            $this->_productIds[$itemId] = 427;
        } else if ($itemId == 'IT544') {
            $this->_productIds[$itemId] = 428;
        } else if ($itemId == 'IT545') {
            $this->_productIds[$itemId] = 429;
        }
    }
}
