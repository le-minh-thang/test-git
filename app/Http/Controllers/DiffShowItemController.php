<?php

namespace App\Http\Controllers;

use App\Product;
use App\MasterItemType;
use Illuminate\Support\Facades\Input;

class DiffShowItemController extends Controller
{
    /**
     * DiffShowItemController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * No name
     */
    public function index()
    {
        $data = Input::all();
        $same = [];

        if (isset($data['up_t_ids'], $data['budget_ids'])) {
            try {
                $data['up_t_ids']   = explode(',', str_replace(" ", '', str_replace("'", '', $data['up_t_ids'])));
                $data['budget_ids'] = explode(',', str_replace(" ", '', str_replace("'", '', $data['budget_ids'])));

                $upTItems = MasterItemType::select('name as title', 'id')
                    ->whereIn('id', $data['up_t_ids'])
                    ->pluck('title', 'id')->toArray();

                var_dump("Total items of Up T: " . count($upTItems));

                $budgetItems = Product::select('title', 'id')
                    ->whereIn('id', $data['budget_ids'])
                    ->whereIn('id', $data['budget_ids'])
                    ->pluck('title', 'id')->toArray();

                var_dump("Total items of Budget: " . count($budgetItems));

                if (empty($budgetItems) || empty($upTItems)) {
                    dd('the item of up-t or the item of budget is empty');
                }

                foreach ($upTItems as $uptKey => $upTItem) {
                    foreach ($budgetItems as $budgetKey => $budgetItem) {
                        if ($upTItem == $budgetItem) {
                            $same[$uptKey] = $budgetKey;
                            unset($upTItems[$uptKey], $budgetItems[$budgetKey]);
                            break;
                        }
                    }
                }

                var_dump('The rest items of up-t');
                var_dump($upTItems);

                var_dump('The rest items of budget ');
                var_dump($budgetItems);

                var_dump('The same');
                var_dump($same);

            } catch (\Exception $exception) {
                dd($exception);
                dd('Error! Exception');
            }
        } else {
            dd('Error! The parameters not match');
        }
    }

    /**
     * Update delete field from Up-T to Budgets
     */
    public function updateDeleteField()
    {
        $data              = Input::all();
        $productsUpdated   = [];
        $upTDeletedItems   = [];
        $upTItemNotMatches = [];

        if (isset($data['up_t_ids'])) {
            try {
                $data['up_t_ids'] = explode(',', str_replace(" ", '', str_replace("'", '', $data['up_t_ids'])));

                $upTItems = MasterItemType::select('name as title', 'id', 'item_code as code', 'state')
                    ->whereIn('id', $data['up_t_ids'])
                    ->get();

                if (empty($upTItems)) {
                    dd('the item of up-t is empty');
                }

                foreach ($upTItems as $upTItem) {
                    $products = Product::where('title', $upTItem->title)->where('code', $upTItem->code)->get();
                    if (!empty($products)) {
                        $this->_updateProductField($products, $upTItem, $productsUpdated, $upTDeletedItems);
                    } else {
                        $upTItemNotMatches[$upTItem->id] = [
                            'title' => $upTItem->title,
                            'state' => $upTItem->state,
                        ];
                    }
                }

                var_dump("Total items of Up T: " . count($upTItems));
                var_dump("Total items of Budget has matched: " . count($productsUpdated));
                var_dump('The items was deleted in Up T DB');
                var_dump($upTDeletedItems);
                var_dump('The not match items of up-t');
                var_dump($upTItemNotMatches);


                var_dump('The product has been updated successfully');
                var_dump($productsUpdated);

            } catch (\Exception $exception) {
                dd($exception);
                dd('Error! Exception');
            }
        } else {
            dd('Error! The parameters not match');
        }
    }

    /**
     * Update is_deleted field
     *
     * @param $products
     * @param $upTState
     * @param $productsUpdated
     */
    private function _updateProductField($products, $upTItem, &$productsUpdated, &$upTDeletedItems)
    {
        if ($upTItem->state == 1) {
            $delete = 0;
        } else {
            $delete                        = 1;
            $upTDeletedItems[$upTItem->id] = [
                'state' => $upTItem->state,
                'title' => $upTItem->title,
            ];
        }
        foreach ($products as $product) {
            $product->is_deleted = $delete;
            $product->save();
            $productsUpdated[$product->id] = [
                'delete_param' => $upTItem->state,
                'is_deleted'   => $product->is_deleted,
                'title'        => $product->title,
            ];
        }
    }

    /**
     * Get diff products
     */
    public function diffProducts()
    {
        $data = Input::all();
        if (isset($data['product_ids'], $data['category_id'])) {
            $products            = Product::where('category_id', $data['category_id'])
                ->select('id', 'title')
                ->orderBy('id')
                ->pluck('title', 'id')->toArray();
            $data['product_ids'] = explode(',', str_replace(" ", '', str_replace("'", '', $data['product_ids'])));

            $productIds = array_keys($products);
            var_dump("count products: " . count($productIds));
            var_dump("count products input: " . count($data['product_ids']));
            $diff = array_diff($productIds, $data['product_ids']);
            var_dump($diff);
            $diffWithtile = [];
            if ($diff) {
                foreach ($diff as $id) {
                    $diffWithtile[$id] = $products[$id];
                }
            }
            var_dump('title: ');
            var_dump($diffWithtile);
            var_dump(sprintf('count diff: %s', count($diff)));
            var_dump($productIds);
            var_dump($data['product_ids']);
        } else {
            var_dump('Error');
        }
    }

    /**
     * Get diff products
     */
    public function shortDiffProducts()
    {
        dd('chưa dùng');
        $input = Input::all();
        if (isset($input['data'])) {
            $input                = explode(':', $input['data']);
            $input['product_ids'] = explode(',', str_replace(" ", '', str_replace("'", '', $input['product_ids'])));
            $products             = Product::where('category_id', $input['category_id'])
                ->select('id', 'title')
                ->orderBy('id')
                ->pluck('title', 'id')->toArray();


            $productIds = array_keys($products);
            var_dump("count products: " . count($productIds));
            var_dump("count products input: " . count($input['product_ids']));
            $diff = array_diff($productIds, $input['product_ids']);
            var_dump($diff);
            $diffWithtile = [];
            if ($diff) {
                foreach ($diff as $id) {
                    $diffWithtile[$id] = $products[$id];
                }
            }
            var_dump('title: ');
            var_dump($diffWithtile);
            var_dump(sprintf('count diff: %s', count($diff)));
            var_dump($productIds);
            var_dump($input['product_ids']);
        } else {
            var_dump('Error');
        }
    }
}
