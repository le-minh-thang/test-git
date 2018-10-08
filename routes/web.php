<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('insert', 'InsertController@index')->name('insert');
Route::get('updating-colors', 'UpdateColorController@index')->name('updateColors');
Route::get('generate-product-for-import-wp', 'GenerateDataForImportWPController@generateProducts')->name('generateDataGenerateProductsImportWP');
//Route::get('insert-from-printty', 'InsertController@insertFromPrintty')->name('insertFromPrintty');
Route::get('update-product', 'UpdateController@index')->name('update');
Route::get('update-budget-product', 'UpdateBudgetItemController@index')->name('updateOther');
Route::get('diff-items', 'DiffShowItemController@index')->name('DiffShowItem');
Route::get('update-delete-field', 'DiffShowItemController@updateDeleteField')->name('UpdateDeleteField');
Route::get('diff-product', 'DiffShowItemController@diffProducts')->name('DiffProduct');
Route::get('short-diff-product', 'DiffShowItemController@shortDiffProducts')->name('ShortDiffProduct');
//Route::get('generate-data-for-import-wp', 'GenerateDataForImportWPController@index')->name('generateDataForImportWP');



// Change up-t connection to orilab connection
Route::get('get-orilabo-price', 'UpdatePriceOrilabController@index')->name('get.price.orilab');
Route::post('update-orilabo-price', 'UpdatePriceOrilabController@update')->name('update.price.orilab');

Route::get('update-nobori', 'GenerateDataForImportWPController@updateNobori')->name('update.nobori');

// Something doesn't relate to this. It has just been found a active link
Route::get('check-link', 'InsertController@checkLink')->name('check.link');

// Update Up T Information of Items
Route::get('updating-items', 'UpdateUpTItemController@update')->name('updateColors');

// update products and color sides
Route::get('updating-product-color-sides', 'UpdateProductController@update')->name('updateColors');

// update products and color sides price
Route::get('updating-product-prices', 'UpdateProductController@updatePrice')->name('updateColors');