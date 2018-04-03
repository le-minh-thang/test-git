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
Route::get('update', 'UpdateController@index')->name('update');
Route::get('update-budget-product', 'UpdateBudgetItemController@index')->name('updateOther');
Route::get('diff-items', 'DiffShowItemController@index')->name('DiffShowItem');
Route::get('update-delete-field', 'DiffShowItemController@updateDeleteField')->name('UpdateDeleteField');
Route::get('diff-product', 'DiffShowItemController@diffProducts')->name('DiffProduct');
Route::get('short-diff-product', 'DiffShowItemController@shortDiffProducts')->name('ShortDiffProduct');
Route::get('generate-data-for-import-wp', 'GenerateDataForImportWPController@index')->name('generateDataForImportWP');
//Route::get('generate-product-for-import-wp', 'GenerateDataForImportWPController@generateProducts')->name('generateDataGenerateProductsImportWP');