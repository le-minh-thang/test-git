<?php

namespace App\Http\Controllers;

use App\MasterItemType;
use App\Product;
use DB;

class UpdateBudgetPlatformProductPriceController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update()
    {
        try {
            DB::beginTransaction();

            $products = Product::with('productColors.productColorSides')
                                ->select('title', 'code', 'id', 'price', 'tool_price')
                                ->where('is_deleted', 0)
                                ->get();

            $itemCounter = $sideCounter = 0;
            foreach ($products as $product) {
                $item = MasterItemType::with('itemSubs.itemSubSides')
                    ->where('name', $product->title)
                    ->where('item_code', $product->code)
                    ->first();

                if ($item) {
                    $item->item_price = $product->price;
                    $item->tool_price = $product->tool_price;

                    $itemCounter += 1;
                    $item->save();

                    foreach ($item->itemSubs as $itemSub) {
                        foreach ($product->productColors as $productColor) {
                            if ($itemSub->color == $productColor->value) {
                                $sideCounter += 1;
                                foreach ($productColor->productColorSides as $productColorSide) {
                                    if (!empty($productColorSide->order) && $productColorSide->order <= 3) {
                                        $side = "cost{$productColorSide->order}";
                                        $itemSub->$side = $productColorSide->print_price;
                                    }
                                }

                                $itemSub->save();

                                break;
                            }

                        }
                    }
                }
            }

            print_r('item counter: ' . $itemCounter);
            echo '<br />';
            print_r('color counter: ' . $sideCounter);
            DB::commit();
            dd('done');
        } catch (\Exception $exception) {
            DB::rollBack();
            var_dump("something went wrong");
            dd($exception);
        }
    }
}
