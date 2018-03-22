<?php

namespace App\Http\Controllers;

use App\Product;
use App\MasterItemType;
use Illuminate\Support\Facades\Input;

class DiffShowItemController extends Controller
{
    /**
     * No name
     */
    public function index()
    {
        ini_set('max_execution_time', 666);
        set_time_limit(666);
        ini_set('memory_limit', '2048M');
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
        ini_set('max_execution_time', 666);
        set_time_limit(666);
        ini_set('memory_limit', '2048M');
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
}
