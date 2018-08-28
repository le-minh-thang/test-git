<?php

namespace App\Http\Controllers;

use App\Category;
use App\MasterItemType;
use App\Product;

class GenerateDataForImportWPController extends Controller
{
    public function generateProducts()
    {
        ini_set('max_execution_time', 666);
        ini_set('memory_limit', '2048M');

        $categoryIds = explode(',', '1,2,3,4,5,7,8,10,11,12,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52');

        // Ids of new items
        $productIds = [];

        // It is set belong to the last id of post id from wp DB plus one
        // The lazy person am I so I decided to choose the the smallest id of new items equal to the last id of Wordpress posts ->
        // It is bigger than the last id of Wordpress posts very much so the site won't be crashed
        $lastPostId = 360;
        for ($i = $lastPostId; $i <= 379; $i++) {
            $productIds[] = $i;
        }

        $categories = Category::select('id', 'title')->with([
            'products' => function ($q) use ($productIds) {
                $q->with(['productColorMain.productColorSideMain', 'productColors.productColorSides'])
                    ->whereIn('id', $productIds);
            },
        ])->whereIn('id', $categoryIds)->whereHas('products', function ($q) use ($productIds) {
            $q->whereIn('id', $productIds);
        })->get();

        $fileName = sprintf('budgets.wordpress.%s.xml', date('Y-m-d-H-i-s'));

        var_dump('Generating products for import from Wordpress successfully!');
        return response((string)view('products.products-to-import', compact('lastPostId', 'categories')), 200, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ]);
    }

    /**
     * Update nobori of products
     */
    public function updateNobori()
    {
        ini_set('max_execution_time', 666);
        set_time_limit(666);
        ini_set('memory_limit', '2048M');

        $masterItemTypeIds = ['IT303', 'IT304', 'IT305', 'IT306', 'IT307', 'IT308', 'IT309', 'IT310', 'IT311', 'IT312', 'IT313', 'IT314', 'IT315', 'IT316', 'IT317', 'IT318', 'IT319', 'IT320', 'IT321', 'IT322', 'IT323', 'IT324', 'IT325', 'IT326', 'IT327', 'IT328', 'IT329', 'IT330', 'IT331', 'IT442', 'IT443', 'IT444', 'IT445', 'IT446', 'IT447', 'IT448', 'IT449', 'IT450', 'IT451', 'IT452', 'IT453', 'IT454', 'IT455', 'IT456', 'IT457', 'IT458', 'IT459', 'IT460', 'IT461', 'IT462'];
        $foundProducts     = [];
        $notFoundProducts  = [];
        $countItem         = 0;
        $countfoundPoduct  = 0;

        foreach ($masterItemTypeIds as $itemTypeId) {
            $nobori = MasterItemType::select('name as title', 'item_code as code')
                ->where('id', $itemTypeId)
                ->first();

            if ($nobori) {
                $countItem += 1;
                $product   = Product::where('code', $nobori->code)->where('title', $nobori->title)->first();

                if ($product) {
                    $countfoundPoduct   += 1;
                    $product->is_nobori = 1;
                    $product->save();
                    $foundProducts[$nobori->code] = $nobori->title;
                } else {
                    $notFoundProducts[$nobori->code] = $nobori->title;
                }
            }
        }

        var_dump('count $masterItemTypeIds', count($masterItemTypeIds));
        var_dump('count found master Item Type ', $countItem);
        var_dump('found', $foundProducts);
        var_dump('not found', $notFoundProducts);
    }
}
