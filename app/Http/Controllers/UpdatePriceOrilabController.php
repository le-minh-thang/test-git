<?php

namespace App\Http\Controllers;

use DB;
use App\MasterItemType;

class UpdatePriceOrilabController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('update-price');
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
        ini_set('max_execution_time', 666);
        set_time_limit(666);
        ini_set('memory_limit', '2048M');
        $updatedItems      = [];
        $updatedItemsOther = [];

        try {
            DB::beginTransaction();
            \Excel::load('C:\Users\Admin\Downloads\test.xlsx', function ($reader) use (&$updatedItems, &$updatedItemsOther) {
                $reader->each(function ($data) use (&$updatedItems, &$updatedItemsOther) {
                    $name = $data->name;
                    $nameOther = str_replace('T', 'Ｔ', $data->name);
                    $nameTheOther = str_replace('Ｔ', 'T', $data->name);
                    $item = MasterItemType::where(function ($q) use ($name, $nameOther, $nameTheOther) {
                        $q->where('name', $name)->orWhere('name', $nameOther)->orWhere('name', $nameTheOther);
                    })->where('item_code_nominal', 'like', "%{$data->code}%")->with('itemSubs')->first();

                    if ($item) {
                        $this->updateItem($item, $data);
                        $updatedItems[]               = [
                            'color'     => $data->color,
                            'name_code' => $item->name . ' - ' . $item->item_code_nominal,
                            'price'     => $data->body,
                        ];
                        $updatedItemsOther[$item->id] = $item->name;
                        $this->updateItemSubs($item->itemSubs, $data);
                    }
                });
            });

            DB::commit();
            var_dump($updatedItemsOther);
            dd($updatedItems);
        } catch (\Exception $exception) {
            DB::rollBack();
            dd($exception);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function updateItem($item, $data)
    {
        $item->tool_price = $data->body;
        $item->item_price = $data->body;
        $item->save();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function updateItemSubs($itemSubs, $data)
    {
        if ($itemSubs) {
            foreach ($itemSubs as $itemSub) {
                if (($itemSub->name == 'ホワイト' || $itemSub->color == '#ffffff' || $itemSub->color == '#FFFFFF') && $data->color == 'ホワイト') {
                    $itemSub->cost1 = $data->table;
                    $itemSub->cost2 = $data->back;
                } else {
                    if ($data->color == 'カラー') {
                        $itemSub->cost1 = $data->table;
                        $itemSub->cost2 = $data->back;
                    }
                }

                $itemSub->save();
            }
        }
    }
}
