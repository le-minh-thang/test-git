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

class UpdateController extends Controller
{
    private $_dateTime;
    private $_lastProductColorId;

    public function __construct()
    {
        $this->_dateTime     = Carbon::now()->format('Y-m-d H:i:s');
        $this->_lastProductColorId = ProductColor::orderBy('id', 'desc')->first()->id + 1;
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
            DB::beginTransaction();

            $productSizeInserts      = [];
            $productColorInserts     = [];
            $productColorSideInserts = [];

            $masterItemTypes = MasterItemType::with('itemSubs', 'itemSizes', 'itemSubs.itemSubSides')
                ->whereIn('id', ['IT101', 'IT102'])->get();

            foreach ($masterItemTypes as $code => $item) {
                $product = Product::with('productSizes', 'productColors', 'productColors.productColorSides')
                    ->where('title', $item->name)
                    ->where('code', $item->item_code)
                    ->first();

                if ($product) {
                    var_dump("update the product {$product->title}");
                    $this->updateProduct($item, $product);
                    // ate a product sizes
                    $this->updateProductSizes($product, $item->itemSizes, $productSizeInserts);

                    $this->_updateProductColors($item->itemSubs, $product, $productColorInserts, $productColorSideInserts);

                } else {
                    var_dump('Nothing found');
                }
            }

            ProductSize::insert($productSizeInserts);
            ProductColor::insert($productColorInserts);
            ProductColorSide::insert($productColorSideInserts);

            DB::commit();
            dd('done');
        } catch (\Exception $exception) {
            DB::rollBack();
            var_dump("something went wrong");
            dd($exception);
        }
    }

    private function _updateProductColorSides($itemSubSide, $productColorId, $itemSub, &$productColorSideInserts, $productColorSide = null)
    {
        $order = null;
        if ($itemSub->color == '#ffffff' || $itemSub->color == '#FFFFFF') {
            if ($itemSubSide->title == '表' || $itemSubSide->title == '表裏同じ' || $itemSubSide->title == '左前' || $itemSubSide->title == '右前') {
                $print_price = 1000;
                $order       = 1;
            } else if ($itemSubSide->title == '裏' || $itemSubSide->title == '左後' || $itemSubSide->title == '右後') {
                $print_price = 1000;
                $order       = 2;
            } else {
                $print_price = 1000;
                if ($itemSubSide->title == '左袖') {
                    $order = 3;
                } else if ($itemSubSide->title == '右袖') {
                    $order = 4;
                }
            }
        } else {
            if ($itemSubSide->title == '表' || $itemSubSide->title == '表裏同じ' || $itemSubSide->title == '左前' || $itemSubSide->title == '右前') {
                $print_price = 1500;
                $order       = 1;
            } else if ($itemSubSide->title == '裏' || $itemSubSide->title == '左後' || $itemSubSide->title == '右後') {
                $print_price = 1500;
                $order       = 2;
            } else {
                $print_price = 1500;
                if ($itemSubSide->title == '左袖') {
                    $order = 3;
                } else if ($itemSubSide->title == '右袖') {
                    $order = 4;
                }
            }
        }

        $data = [
            'title'            => $itemSubSide->title,
            'product_color_id' => $productColorId,
            'is_main'          => $itemSubSide->is_main,
            'content'          => $itemSubSide->content,
            'image_url'        => $itemSubSide->image_url,
            'preview_url'      => $itemSubSide->preview_url,
            'print_price'      => $print_price,
            'is_deleted'       => 0,
            'order'            => $order,
            'created_at'       => $itemSubSide->created,
            'updated_at'       => $itemSubSide->modified,
            'content_print'    => $itemSubSide->content_print,
        ];

        if ($productColorSide) {
            $productColorSide->update($data);
        } else {
            $productColorSideInserts[] = $data;
        }
    }


    private function _updateProductColors($itemSubs, $product, &$productColorInserts, &$productColorSideInserts)
    {
        foreach ($itemSubs as $itemSub) {

            $data = [
                'title'      => $itemSub->name,
                'value'      => $itemSub->color,
                'code'       => $itemSub->item_code,
                'product_id' => $product->id,
                'is_main'    => $itemSub->is_main,
                'is_deleted' => 0,
                'created_at' => $itemSub->created,
                'updated_at' => $itemSub->modified,
            ];

            $hasProductColor = false;

            foreach ($product->productColors as $productColor) {

                if ($itemSub->item_code == $productColor->code && $itemSub->color == $productColor->value) {

                    foreach ($itemSub->itemSubSides as $itemSubSide) {
                        $hasProductColorSide = false;
                        foreach ($productColor->productColorSides as $productColorSide) {
                            if ($itemSubSide->title == $productColorSide->title) {
                                $hasProductColorSide = true;
                                $this->_updateProductColorSides($itemSubSide, $productColor->id, $itemSub, $productColorSideInserts, $productColorSide);
                                break;
                            }
                        }
                        if (!$hasProductColorSide) {
                            $this->_updateProductColorSides($itemSubSide, $productColor->id, $itemSub, $productColorSideInserts);
                        }
                    }

                    $productColor->update($data);
                    $hasProductColor = true;
                    break;
                }
            }

            if (!$hasProductColor) {
                $data['id']            = $this->_lastProductColorId;
                $productColorInserts[] = $data;

                foreach ($itemSub->itemSubSides as $itemSubSide) {
                    $this->_updateProductColorSides($itemSubSide, $this->_lastProductColorId, $itemSub, $productColorSideInserts);
                }

                $this->_lastProductColorId += 1;
            }
        }
    }

    private function updateProduct($item, $product)
    {
        if ($product->title == '定番スウェットパンツ') {
            $price     = 2500;
            $toolPrice = 3500;
        } else {
            $price     = 1800;
            $toolPrice = 2800;
        }

        $data = [
            'category_id'      => $item->category_id,
            'title'            => $item->name,
            'code'             => $item->item_code,
            'price'            => $price,
            'is_main'          => $item->is_main,
            'is_deleted'       => 0,
            'order'            => $item->order,
            'created_at'       => $item->created,
            'updated_at'       => $item->modified,
            'tool_price'       => $toolPrice,
            'color_total'      => $item->color_total,
            'size'             => $item->size,
            'sale_price'       => $item->sale_price,
            'item_code_nomial' => $item->item_code_nomial,
            'material'         => $item->material,
            'maker'            => $item->maker,
        ];

        $product->update($data);
    }

    private function updateProductSizes($product, $itemSizes, &$productSizeInserts)
    {
        foreach ($itemSizes as $itemSize) {
            $hasProductSize = false;
            $data           = [
                'product_id' => $product->id,
                'title'      => $itemSize->name,
                'is_main'    => $itemSize->is_main,
                'is_deleted' => $itemSize->is_deleted,
                'created_at' => $itemSize->created_at,
                'updated_at' => $itemSize->updated_at,
            ];

            foreach ($product->productSizes as $productSize) {
                if ($itemSize->name == $productSize->title) {
                    $productSize->update($data);
                    $hasProductSize = true;
                    break;
                }
            }

            if (!$hasProductSize) {
                $productSizeInserts[] = $data;
            }
        }
    }
}
