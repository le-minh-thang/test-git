<?php

namespace App\Http\Controllers;

use App\OrilabMasterItemType;
use DB;
use App\MasterItemType;

class UpdatePriceBudgetController extends Controller
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
        return view('update-budget-price');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update()
    {
        $updatedItems  = [];
        $notFoundItems = [];

        try {
            DB::beginTransaction();
            \Excel::load(request()->prices, function ($reader) use (&$updatedItems, &$notFoundItems) {
                $reader->each(function ($data) use (&$updatedItems, &$notFoundItems) {
                    $name         = $data->name;
                    $price        = (int)$data->price;
                    $otherName    = str_replace('T', 'Ｔ', $data->name);
                    $theOtherName = str_replace('Ｔ', 'T', $data->name);
                    $items        = MasterItemType::where(function ($q) use ($name, $otherName, $theOtherName) {
                        $q->where('name', $name)->orWhere('name', $otherName)->orWhere('name', $theOtherName);
                    })->with([
                                 'itemSubs' => function ($q) {
                                     $q->select('cost1', 'cost2', 'cost3', 'item_type')->orderBy('cost1', 'asc');
                                 },
                             ])->get();

                    if (count($items)) {
                        foreach ($items as $item) {
                            $sidePrices = $item->itemSubs->first()->toArray();
                            unset($sidePrices['item_type']);
                            sort($sidePrices);
                            $toolPrice        = array_shift($sidePrices) + $price;
                            $item->tool_price = $toolPrice;
                            $item->item_price = $price;

                            $item->save();

                            $updatedItems[] = [
                                'name'        => $name,
                                'price'       => $price,
                                'tool_price'  => $toolPrice,
                                'side_prices' => $sidePrices,
                            ];
                        }
                    } else {
                        $notFoundItems[] = $name;
                    }
                });
            });

//            DB::commit();
            var_dump($notFoundItems);
            dd($updatedItems);
        } catch (\Exception $exception) {
            DB::rollBack();
            dd($exception);
        }
    }
}
