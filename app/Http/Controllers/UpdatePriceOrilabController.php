<?php

namespace App\Http\Controllers;

use App\OrilabMasterItemType;
use DB;
use App\MasterItemType;

class UpdatePriceOrilabController extends Controller
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
        $updatedItems      = [];
        $updatedItemsOther = [];

        try {
            DB::beginTransaction();
            \Excel::load('C:\Users\Admin\Downloads\test.xlsx', function ($reader) use (&$updatedItems, &$updatedItemsOther) {
                $reader->each(function ($data) use (&$updatedItems, &$updatedItemsOther) {
                    $name         = $data->name;
                    $otherName    = str_replace('T', 'Ｔ', $data->name);
                    $theOtherName = str_replace('Ｔ', 'T', $data->name);
                    $item         = MasterItemType::where(function ($q) use ($name, $otherName, $theOtherName) {
                        $q->where('name', $name)->orWhere('name', $otherName)->orWhere('name', $theOtherName);
                    })->where('item_code_nominal', 'like', "%{$data->code}%")->with('itemSubs')->first();

                    if ($item) {
                        $this->updateItem($item, $data);
                        $updatedItems[]               = [
                            'color' => $data->color, 'name_code' => $item->name . ' - ' . $item->item_code_nominal, 'price' => $data->body,
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

    public function updatePriceByIds()
    {
        $matches    = [];
        $notMatches = [];
        $ids        = [
            'IT001', 'IT003', 'IT004', 'IT006', 'IT063', 'IT064', 'IT011', 'IT013', 'IT073',
            'IT031', 'IT036', 'IT039', 'IT032', 'IT034', 'IT058', 'IT041', 'IT103', 'IT104',
            'IT116', 'IT124', 'IT322', 'IT323', 'IT357', 'IT358', 'IT377', 'IT367', 'IT392',
            'IT393', 'IT442', 'IT443', 'IT445', 'IT448', 'IT449', 'IT450', 'IT451', 'IT452',
            'IT453', 'IT454', 'IT455', 'IT456', 'IT457', 'IT458', 'IT459', 'IT460', 'IT461',
            'IT462', 'IT497', 'IT498', 'IT499', 'IT489', 'IT579',
        ];

        try {
            DB::beginTransaction();

            $masterItemTypes = MasterItemType::with('itemSubs')
                ->whereIn('id', $ids)
                ->get();

            foreach ($masterItemTypes as $masterItemType) {
                $orilabMasterItemType = OrilabMasterItemType::with('itemSubs')->find($masterItemType->id);

                if ($orilabMasterItemType) {
                    $matches[$masterItemType->id] = [
                        'name'   => $masterItemType->name,
                        'master' => [
                            'tool_price' => $masterItemType->tool_price,
                            'item_price' => $masterItemType->item_price,
                        ],
                        'orilab' => [
                            'tool_price' => $orilabMasterItemType->tool_price,
                            'item_price' => $orilabMasterItemType->item_price,
                        ],
                    ];

                    $orilabMasterItemType->tool_price = $masterItemType->tool_price;
                    $orilabMasterItemType->item_price = $masterItemType->item_price;

                    //$orilabMasterItemType->save();

                    foreach ($masterItemType->itemSubs as $itemSub) {
                        $i = 1;

                        foreach ($orilabMasterItemType->itemSubs as $orilabItemSub) {
                            if ($itemSub->id == $orilabItemSub->id) {
                                $matches[$masterItemType->id]['items'][] = [
                                    'master' => [
                                        'cost1' => $itemSub->cost1,
                                        'cost2' => $itemSub->cost2,
                                        'cost3' => $itemSub->cost3,
                                    ],
                                    'orilab' => [
                                        'cost1' => $orilabItemSub->cost1,
                                        'cost2' => $orilabItemSub->cost2,
                                        'cost3' => $orilabItemSub->cost3,
                                    ],
                                ];

                                $orilabItemSub->cost1 = $itemSub->cost1;
                                $orilabItemSub->cost2 = $itemSub->cost2;
                                $orilabItemSub->cost3 = $itemSub->cost3;

                                //$orilabItemSub->save();

                                break;
                            }
                        }

                        ++$i;

                        if ($i == count($masterItemType->itemSubs) && count($masterItemType->itemSubs) != $matches[$masterItemType->id]['items']) {
                            $notMatches[$masterItemType->id][] = 'item sub is different';
                        }
                    }
                } else {
                    $notMatches[$masterItemType->id] = $masterItemType->name;
                }
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            var_dump("something went wrong");
            dd($exception);
        }

        echo '<pre>';
        print_r($notMatches);
        echo '</pre>';
        dd($matches);
    }
}
