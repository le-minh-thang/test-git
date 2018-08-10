<?php

namespace App\Http\Controllers;

use App\Category;
use App\MasterItemType;
use App\Product;

class GenerateDataForImportWPController extends Controller
{
    public function index()
    {
        $lastPostId = 72;
        $categories = json_decode(file_get_contents(public_path('js\products.json')), true);
        $products   = [];
        foreach ($categories as $category) {
            foreach ($category['items'] as $product) {
                $products[$product['id']] = $product['name'];
            }
        }

//        $productIds = explode(',', '1, 68, 63, 78, 41, 73, 3, 64, 74, 77, 4, 40, 6, 245, 237, 238, 252, 240, 241, 242, 243, 253, 255, 256, 257, 258, 239, 276,10, 45, 11, 75, 12,18, 54, 47, 48, 49, 50, 55, 19, 278, 280,25, 26, 21, 22, 29, 28,24, 23, 27,221, 72, 34, 67, 66,207, 35, 36, 58, 59, 37, 218,209, 31, 53,208, 30, 52, 203, 204, 205,69, 16, 71, 76, 254, 236,170, 160, 161, 79, 80, 81, 82, 83, 84,217, 218, 85, 86, 87, 88, 89, 90, 91, 92, 93, 95, 96, 97, 99, 100, 94, 98, 101, 104, 102, 105, 103, 106, 107, 108, 109, 110, 219, 220, 221, 222, 224,171, 172, 162, 163, 164, 165, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122,259, 260, 261, 262, 263, 210, 211, 212, 213, 214, 215, 216, 123, 124, 125, 126, 127, 128, 129, 230, 131, 133, 134, 135, 137, 138, 132, 136, 139, 140, 145, 143, 141, 144, 142, 146, 148, 148, 149, 130, 147,173, 166, 167, 150, 151, 152, 153, 154, 155, 156, 157,158, 159, 274,168, 264,169,174, 175, 176, 177, 178, 180,182, 183, 184, 185, 186, 187,188, 189, 190,191, 192, 193, 194, 195,196, 197, 198, 200, 201, 202,222, 223,228, 229, 230, 231,224, 225, 226, 227,244,56, 57,265, 266, 267,77, 237, 277, 279,272, 273,275,281');
//        $allProducts = Product::whereIn('id', $productIds)->select('id', 'title')->pluck('title', 'id')->toArray();
//        var_dump("count products: ". count($products));
//        var_dump("count all products: ". count($allProducts));
//        $diffs           = array_diff_assoc($allProducts, $products);
//        $a = 1;
        return view('products.product-string-to-import', compact('lastPostId', 'categories'));
    }

    public function generateProducts()
    {
        ini_set('max_execution_time', 666);
//        set_time_limit(666);
        ini_set('memory_limit', '2048M');

        $categoryIds = explode(',', '1,2,3,4,5,7,8,10,11,12,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52');
        //$productIds  = explode(',', '293,294,295,296,297,298,300,301,302,303,304,305,306,307');
        $productIds  = [];
        for ($i = 352; $i <= 359; $i++) {
            $productIds[] = $i;
        }

        // Set it belong to the last id of post id from wp DB plus one
        $lastPostId = 348;

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
