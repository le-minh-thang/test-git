<?php

namespace App\Http\Controllers;

use App\Category;
use App\Product;

class GenerateDataForImportWPController extends Controller
{
    public function index()
    {
        $lastPostId = 72;
        $categories = json_decode(file_get_contents(public_path('js\products.json')), true);
        return view('products.product-string-to-import', compact('lastPostId', 'categories'));
    }

    public function generateProducts()
    {
        $productIds = explode(',', "1,68,63,78,41,73,3,64,74,77,4,40,6,245,237,238,252,240,241,242,243,253,255,256,257,258,239,10,45,11,75,12,18,54,47,48,49,50,55,19,25,26,21,22,29,28,24,23,27,221,72,34,67,66,207,35,36,,58,59,37,218,209,31,53,208,30,52,203,204,205,69,16,71,76,254,236,170,160,161,79,80,81,82,83,84,217,218,85,86,87,88,89,90,91,92,93,95,96,97,99,100,94,98,101,104,102,105,103,106,107,108,109,110,219,220,221,222,224,171,172,162,163,164,165,111,112,113,114,115,116,117,118,119,120,121,122,259,260,261,262,263,210,211,212,213,214,215,216,123,124,125,126,127,128,129,230,131,133,134,135,137,138,132,136,139,140,145,143,141,144,142,146,148,148,149,130,147,173,166,167,150,151,152,153,154,155,156,157,158,159,168,264,169,174,175,176,177,178,180,182,183,184,185,186,187,188,189,190,191,192,193,194,195,196,197,198,200,201,202,222,223,228,229,230,231,224,225,226,227,244,56,57,265,266,267,77,237");
        $lastPostId = 71;

        $categories = Category::with(['products' => function ($q) use ($productIds) {
            $q->whereIn('id', $productIds);
        }])->get();

        dd('Nope');

        return view('products.products-to-import', compact('lastPostId', 'categories'));
    }
}
