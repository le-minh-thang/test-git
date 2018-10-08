<?php

namespace App\Http\Controllers;

use App\Product;
use Illuminate\Support\Facades\Input;

class UpdateBudgetItemController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = Input::all();
        if (isset(
            $data['id'], $data['price'], $data['tool_price'],
            $data['white_front_fee'], $data['white_back_fee'],
            $data['white_sleeve_fee'], $data['color_front_fee'],
            $data['color_back_fee'], $data['color_sleeve_fee']
        )) {
            try {
                $product = Product::with('productColors.productColorSides')
                    ->where('id', $data['id'])
                    ->first();
                if ($product) {
                    var_dump("Update the product {$product->title}");
                    $this->_updateProduct($product, $data);
                    var_dump("Update the product color side");
                    $this->_updateProductColorSide($product->productColors, $data);
                    var_dump("Update the product {$product->title} done");
                } else {
                    dd('Not found');
                }
            } catch (\Exception $exception) {
                dd('Error! Exception');
            }
        } else {
            dd('Error! The parameters not match');
        }
    }

    private function _updateProduct($product, $data)
    {
        $product->price      = $data['price'];
        $product->tool_price = $data['tool_price'];
        $product->save();
    }

    private function _updateProductColorSide($productColors, $data)
    {
        foreach ($productColors as $productColor) {
            foreach ($productColor->productColorSides as $productColorSide) {
                $print_price = null;
                if ($productColor->value == '#ffffff' || $productColor->value == '#FFFFFF') {
                    if ($productColorSide->title == '表' || $productColorSide->title == '表裏同じ' || $productColorSide->title == '左前' || $productColorSide->title == '右前') {
                        $print_price = $data['white_front_fee'];
                    } else if ($productColorSide->title == '裏' || $productColorSide->title == '左後' || $productColorSide->title == '右後') {
                        $print_price = $data['white_back_fee'];
                    } else if ($productColorSide->title == '右前' || $productColorSide->title == '左袖' || $productColorSide->title == '右袖' || $productColorSide->title == '両面同一') {
                        $print_price = $data['white_sleeve_fee'];
                    }
                } else {
                    if ($productColorSide->title == '表' || $productColorSide->title == '表裏同じ' || $productColorSide->title == '左前' || $productColorSide->title == '右前') {
                        $print_price = $data['color_front_fee'];
                    } else if ($productColorSide->title == '裏' || $productColorSide->title == '左後' || $productColorSide->title == '右後') {
                        $print_price = $data['color_back_fee'];
                    } else if ($productColorSide->title == '右前' || $productColorSide->title == '左袖' || $productColorSide->title == '右袖' || $productColorSide->title == '両面同一') {
                        $print_price = $data['color_sleeve_fee'];
                    }
                }
                if (!empty($print_price)) {
                    $productColorSide->print_price = $print_price;
                    $productColorSide->save();
                } else {
                    var_dump("Product color side {$productColorSide->id} hasn't updated yet");
                }
            }
        }
    }
}
