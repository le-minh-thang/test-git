<?php

namespace App\Http\Controllers;

use App\MasterItemType;
use App\Product;
use Illuminate\Http\Request;
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
        $data  = Input::all();
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
}
