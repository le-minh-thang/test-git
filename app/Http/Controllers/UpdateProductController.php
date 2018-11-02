<?php

namespace App\Http\Controllers;

use App\MasterItemType;
use App\Product;
use Illuminate\Http\Request;

class UpdateProductController extends Controller
{
    private $_productPrices = [];

    public function __construct()
    {
        parent::__construct();

        $this->_setProductPrices();
    }

    /**
     * Update the products in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update()
    {
        $matches = [];
        $items   = ['IT223', 'IT225', 'IT265', 'IT267'];

        try {
            foreach ($items as $itemId) {
                $masterItemType = MasterItemType::select('name as title', 'item_code as code', 'id')
                    ->where('id', $itemId)
                    ->first();

                $products = Product::where('title', $masterItemType->title)->where('code', $masterItemType->code)->get();

                foreach ($masterItemType->itemSubs as $itemSub) {
                    foreach ($products as $product) {
                        foreach ($product->productColors as $productColor) {
                            if ($productColor->code == $itemSub->item_code) {
                                foreach ($itemSub->itemSubSides as $itemSubSide) {
                                    foreach ($productColor->productColorSides as $productColorSide) {
                                        if ($itemSubSide->title == $productColorSide->title) {
                                            $matches[] = [
                                                'category'    => $product->category_id,
                                                'name'        => $product->title,
                                                'old_preview' => $productColorSide->preview_url,
                                                'new_preview' => $itemSubSide->preview_url,
                                                'old_picture' => $productColorSide->image_url,
                                                'new_picture' => $itemSubSide->image_url,
                                                'message'     => sprintf('item: %s, color: %s (code: %s), color side: %s', $product->id, $productColor->id, $productColor->code, $productColorSide->id),
                                            ];

                                            $productColorSide->content       = $itemSubSide->content;
                                            $productColorSide->image_url     = $itemSubSide->image_url;
                                            $productColorSide->preview_url   = $itemSubSide->preview_url;
                                            $productColorSide->content_print = $itemSubSide->content_print;

                                            $productColorSide->save();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            dd($matches);
        } catch (\Exception $exception) {
            var_dump("something went wrong");
            dd($exception);
        }
    }

    /**
     * Update the product prices
     */
    public function updatePrice()
    {
        $matches = [];

        try {
            foreach ($this->_productPrices as $productId => $prices) {

                $product = Product::where('id', $productId)->first();

                $product->price      = $prices['price'];
                $product->tool_price = $prices['tool_price'];

                $product->save();

                $matches[] = sprintf('Updated product: %s. %s (price: %s, tool price: %s)', $product->id, $product->title, $prices['price'], $prices['tool_price']);
            }

            dd($matches);
        } catch (\Exception $exception) {
            var_dump("something went wrong");
            dd($exception);
        }
    }

    /**
     * Set product and print product prices
     * Everything is different from add items file
     */
    private function _setProductPrices()
    {
        $this->_productPrices = [
            '395'    => [
                'price'      => 2400,
                'tool_price' => 2400,
            ], '396' => [
                'price'      => 1000,
                'tool_price' => 1000,
            ], '398' => [
                'price'      => 2100,
                'tool_price' => 2100,
            ], '399' => [
                'price'      => 1600,
                'tool_price' => 1600,
            ], '400' => [
                'price'      => 1400,
                'tool_price' => 1400,
            ], '401' => [
                'price'      => 1100,
                'tool_price' => 1100,
            ], '402' => [
                'price'      => 1300,
                'tool_price' => 1300,
            ], '403' => [
                'price'      => 1200,
                'tool_price' => 1200,
            ],
        ];

        $this->_productPrices['397'] = $this->_productPrices['396'];
        $this->_productPrices['404'] = $this->_productPrices['403'];
        $this->_productPrices['405'] = $this->_productPrices['403'];
    }

}
