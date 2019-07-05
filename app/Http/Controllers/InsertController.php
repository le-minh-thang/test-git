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

                $masterItemTypes = MasterItemType::select('id')
                    ->where('category_id', '>', 0)
//                ->where('id', '<>', 'IT489')
                    ->where('id', '>', 'IT524')
                    ->pluck('id', 'id')
                    ->toArray();


                $products = Product::select('id')->pluck('id', 'id')->toArray();
                $diffs    = array_diff($masterItemTypes, $products);

                $ids = '';
                foreach ($diffs as $id) {
                    $ids .= sprintf('"%s",', $id);
                }
                dd($ids);
                // Unless self insult, please dump and die $diffs before add items
                // dd($diffs);

                foreach ($diffs as $id) {
                    $item = MasterItemType::with('itemSizes', 'itemSubs.itemSubSides')->find($id);

                    $this->_generateProduct($item, $productInserts);

                    var_dump("generate the product {$item['id']}");

                    $this->_generateProductSizes($productSizeInserts, $item->itemSizes);

                    foreach ($item->itemSubs as $itemSub) {
                        $this->_addItem($itemSub, $productColorInserts);

                        foreach ($itemSub->itemSubSides as $itemSubSide) {
                            $this->_addItem($itemSubSide, $productColorSideInserts);
                        }
                    }
                }

                // Insert printty product
//            foreach ($printtyProducts as $printtyProductTableName => $upTPrinttyProductTableName) {
//                $rinttyProductClass       = "\App\\{$printtyProductTableName}";
//                $upTPrinttyProductClass   = "\App\\{$upTPrinttyProductTableName}";
//                $printtyProductTableIds   = $rinttyProductClass::select('id')->pluck('id', 'id')->toArray();
//                $upTrinttyProductTableIds = $upTPrinttyProductClass::select('id')->pluck('id', 'id')->toArray();
//                $diffPrinttyProducts      = array_diff_assoc($upTrinttyProductTableIds, $printtyProductTableIds);
//                if (!empty($diffPrinttyProducts)) {
//                    ${lcfirst($printtyProductTableName) . 'Inserts'} = $upTPrinttyProductClass::whereIn('id', $diffPrinttyProducts)->get()->toArray();
//                }
//            }

                dd($productInserts);

                //DB::beginTransaction();

//            Product::insert($productInserts);
//            ProductSize::insert($productSizeInserts);
//            ProductColor::insert($productColorInserts);
//            ProductColorSide::insert($productColorSideInserts);
//            // Insert printty product
//            PrinttyProduct::insert($printtyProductInserts);
//            PrinttyProductSize::insert($printtyProductSizeInserts);
//            PrinttyProductColor::insert($printtyProductColorInserts);
//            PrinttyProductColorSide::insert($printtyProductColorSideInserts);

                //DB::commit();
                dd('done');
            } catch (\Exception $exception) {
                DB::rollBack();
                var_dump("something went wrong");
                dd($exception);
            }
        }

        /**
         * Prepare product data
         *
         * @param $item
         * @param $lastProductId
         * @return array
         */
        private function _generateProduct($item, &$productInserts)
        {
            $prices = [
                'IT663' => [
                    'price'      => 1600,
                    'tool_price' => 1600,
                ],
                'IT671' => [
                    'price'      => 1700,
                    'tool_price' => 1700,
                ],
                'IT672' => [
                    'price'      => 1400,
                    'tool_price' => 1400,
                ],
            ];

            if (!empty($prices[$item['id']])) {
                $item->price      = $prices[$item['id']]['price'];
                $item->tool_price = $prices[$item['id']]['tool_price'];
            }

            $this->_addItem($item, $productInserts);
        }

        private function _addItem($item, &$itemInserts)
        {
            foreach ($item as $key => $value) {
                $itemInserts[$item['id']][$key] = $value;
            }
        }

        /**
         * @param $productSizeInserts
         * @param $lastProductId
         * @param $itemSizes
         * @param $lastProductSizeOrder
         */
        private function _generateProductSizes(&$productSizeInserts, $itemSizes)
        {
            foreach ($itemSizes as $itemSize) {
                $this->_addItem($itemSize, $productSizeInserts);
            }
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

        public function mappingItems()
        {
            // 12958 => "たすき180mm幅", 12955 => "たすき150mm幅"
            $wpProducts = [12958 => 'たすき180mm幅', 12955 => 'たすき150mm幅', 12951 => '手旗大', 12949 => '手旗小', 12947 => '屋外用バナースタンドB60'];

            // 'たすき180mm幅', 'たすき150mm幅'
            $excelProducts = ['即日Tシャツ', 'プレミアムTシャツ', 'ハイグレードTシャツ', 'ラグランTシャツ', 'ハニカムメッシュTシャツ'];

            // 'たすき180mm幅', 'たすき150mm幅'
            $dbProducts = ['IT001' => '定番Tシャツ', 'IT003' => '軽量Tシャツ', 'IT004' => 'ハイグレードTシャツ', 'IT005' => 'ライトオーガニックTシャツ', 'IT006' => 'ラグランTシャツ'];

            $category_ids = ['IT001' => '1','IT018' => '12','IT011' => '2','IT681' => '20','IT682' => '19','IT683' => '19'];


            $this->_replaceTchar($wpProducts);
            $this->_replaceTchar($excelProducts);

            $query = '';
            $existItems     = array_flip(array_diff($excelProducts, array_diff($excelProducts, $dbProducts)));
            $dbProductNames = array_flip($dbProducts);

            $i = 0;
            foreach ($wpProducts as $key => $product) {
                if (!empty($existItems[$product])) {
                    $i++;
                    $query .= sprintf("INSERT INTO `master_item_type_orilab`(`id_item_orilab`, `id_item_plaform`, `id_item_color`, `category`, `website`) VALUES ('%s', '%s', NULL, %s, '13');", $key, $dbProductNames[$product], $category_ids[$dbProductNames[$product]]);
                }
            }
var_dump(count($existItems), $i);
            dd($query);
            var_dump(count($excelProducts));
            var_dump(count($wpProducts));
            dd();
        }

        private function _replaceTchar(&$array)
        {
            foreach ($array as $key => $value) {
                $value = str_replace('T', 'T', $value);
//                $value = str_replace(' T', 'T', $value);
                $value = str_replace(' B', '　B', $value);
                $value = str_replace(' A', '　A', $value);
                $value = str_replace(' (', '（', $value);
//                $value = str_replace(')', '）', $value);
                $array[$key] = $value;
            }
        }

        public function getWPProducts()
        {
            // select ids from mit_orilab
            $products = wc_get_products([
                                            'limit'   => -1,
                                            'exclude' => [
                                                1189,
                                                1179,
                                                1180
                                            ],
                                        ]);

            $result = '';

            foreach ($products as $product) {
                $result .= sprintf(', %s => "%s"', $product->id, str_replace('T', 'T', $product->name));
            }

            print_r($result);

            // get item names
            $excel_products = "定番Tシャツ
,軽量Tシャツ";
            $excel_products = explode(',', $excel_products);

            $string = '';
            foreach ($excel_products as $excel_product) {
                $string .= sprintf(',"%s"', trim(str_replace('T', 'Ｔ', $excel_product)));
            }
            print_r($string);
        }

        private function _getProducts()
        {
            $excel_products =  'IT001=>定番Tシャツ
                                IT003=>軽量Tシャツ
                                IT004=>ハイグレードTシャツ
                                IT005=>ライトオーガニックTシャツ
                                IT006=>ラグランTシャツ
                                IT007=>レディースフィットTシャツ';

            $excel_products = str_replace('IT', '=>IT', $excel_products);
            $excel_products = str_replace('=>IT001', 'IT001', $excel_products);
            $excel_products = explode('=>', trim($excel_products));

            $string = '';

            foreach ($excel_products as $key => $excel_product) {
                if ($key % 2 == 0) {
                    $string .= sprintf(",'%s' => '%s'", $excel_product, $excel_products[$key + 1]);
                }
            }
            print_r($string);
        }
    }
