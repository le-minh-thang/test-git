<?php
/**
 * Created by PhpStorm.
 * User: KietTV
 * Date: 3/14/2018
 * Time: 10:45 AM
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel;

class AddItemController extends Controller
{
	public function addItem(){

		$file_path='C:\xampp\htdocs\tool\tool\dataAddItem\data969.xlsx';

		$master_item_type = $this->excelToArray($file_path,'master_item_type');
		foreach ($master_item_type as $key => $value){
			$master_item_type[$key]['created']=date('Y-m-d H:m:s');
			$master_item_type[$key]['modified']=date('Y-m-d H:m:s');
		}

		$master_item_type_sub = $this->excelToArray($file_path,'master_item_type_sub');
		foreach ($master_item_type_sub as $key => $value){
			$master_item_type_sub[$key]['created']=date('Y-m-d H:m:s');
			$master_item_type_sub[$key]['modified']=date('Y-m-d H:m:s');
		}

		$master_item_type_sub_sides = $this->excelToArray($file_path,'master_item_type_sub_sides');
		foreach ($master_item_type_sub_sides as $key => $value){
			$master_item_type_sub_sides[$key]['created']=date('Y-m-d H:m:s');
			$master_item_type_sub_sides[$key]['modified']=date('Y-m-d H:m:s');
		}

		$master_item_type_size = $this->excelToArray($file_path,'master_item_type_size');
		foreach ($master_item_type_size as $key => $value){
			$master_item_type_size[$key]['created']=date('Y-m-d H:m:s');
			$master_item_type_size[$key]['modified']=date('Y-m-d H:m:s');
		}

		$printty_products = $this->excelToArray($file_path,'printty_products');
		foreach ($printty_products as $key => $value){
			$printty_products[$key]['created']=date('Y-m-d H:m:s');
			$printty_products[$key]['modified']=date('Y-m-d H:m:s');
		}

		$printty_products_colors = $this->excelToArray($file_path,'printty_products_colors');
		foreach ($printty_products_colors as $key => $value){
			$printty_products_colors[$key]['created']=date('Y-m-d H:m:s');
			$printty_products_colors[$key]['modified']=date('Y-m-d H:m:s');
		}

		$printty_products_colors_sides = $this->excelToArray($file_path,'printty_products_colors_sides');
		foreach ($printty_products_colors_sides as $key => $value){
			$printty_products_colors_sides[$key]['created']=date('Y-m-d H:m:s');
			$printty_products_colors_sides[$key]['modified']=date('Y-m-d H:m:s');
		}

		$printty_products_sizes = $this->excelToArray($file_path,'printty_products_sizes');
		foreach ($printty_products_sizes as $key => $value){
			$printty_products_sizes[$key]['created']=date('Y-m-d H:m:s');
			$printty_products_sizes[$key]['modified']=date('Y-m-d H:m:s');
		}
		dd($master_item_type);
		DB::beginTransaction();

		try {

			foreach ($master_item_type as $key => $value){
				$row = DB::table('master_item_type')->where('id',$value['id'])->get();
				if(empty($row)){
					DB::table('master_item_type')->insert($value);
				}
			}
			var_dump('done master_item_type ===> ');
			foreach ($master_item_type_sub as $key => $value){
				$row = DB::table('master_item_type_sub')->where('id',$value['id'])->get();
				if(empty($row)){
					DB::table('master_item_type_sub')->insert($value);
				}
			}
			var_dump('done master_item_type_sub  ===> ');
			foreach ($master_item_type_sub_sides as $key => $value){
				$row = DB::table('master_item_type_sub_sides')->where('id',$value['id'])->get();
				if(empty($row)){
					DB::table('master_item_type_sub_sides')->insert($value);
				}
			}
			var_dump('done master_item_type_sub_sides ===> ');
			foreach ($master_item_type_size as $key => $value){
				$row = DB::table('master_item_type_size')->where('id',$value['id'])->get();
				if(empty($row)){
					DB::table('master_item_type_size')->insert($value);
				}
			}
			var_dump('done master_item_type_size ===> ');
			foreach ($printty_products as $key => $value){
				$row = DB::table('printty_products')->where('id',$value['id'])->get();
				if(empty($row)){
					DB::table('printty_products')->insert($value);
				}
			}
			var_dump('done printty_products ===> ');
			foreach ($printty_products_colors as $key => $value){
				$row = DB::table('printty_products_colors')->where('id',$value['id'])->get();
				if(empty($row)){
					DB::table('printty_products_colors')->insert($value);
				}
			}
			var_dump('done printty_products_colors ===> ');
			foreach ($printty_products_colors_sides as $key => $value){
				$row = DB::table('printty_products_colors_sides')->where('id',$value['id'])->get();
				if(empty($row)){
					DB::table('printty_products_colors_sides')->insert($value);
				}
			}
			var_dump('done printty_products_colors_sides ===> ');
			foreach ($printty_products_sizes as $key => $value){
				$row = DB::table('printty_products_sizes')->where('id',$value['id'])->get();
				if(empty($row)){
					DB::table('printty_products_sizes')->insert($value);
				}
			}
			var_dump('done printty_products_sizes ===> ');
			DB::commit();
			// all good
		} catch (\Exception $e) {
			DB::rollback();
			dd('insert err');
			// something went wrong
		}

		dd('done insert');
	}

	public function updateItem(){

		$file_path='C:\xampp\htdocs\tool\tool\dataAddItem\data969update.xlsx';

		$master_item_type = $this->excelToArray($file_path,'master_item_type');
		foreach ($master_item_type as $key => $value){
			$master_item_type[$key]['modified']=date('Y-m-d H:m:s');
			foreach ($master_item_type[$key] as $key2 => $value2){
				if(empty($master_item_type[$key][$key2])){
					unset($master_item_type[$key][$key2]);
				}
			}
		}

		$master_item_type_sub = $this->excelToArray($file_path,'master_item_type_sub');
		foreach ($master_item_type_sub as $key => $value){
			$master_item_type_sub[$key]['modified']=date('Y-m-d H:m:s');
			foreach ($master_item_type_sub[$key] as $key2 => $value2){
				if(empty($master_item_type_sub[$key][$key2])){
					unset($master_item_type_sub[$key][$key2]);
				}
			}
		}

		$master_item_type_sub_sides = $this->excelToArray($file_path,'master_item_type_sub_sides');
		foreach ($master_item_type_sub_sides as $key => $value){
			$master_item_type_sub_sides[$key]['modified']=date('Y-m-d H:m:s');
			foreach ($master_item_type_sub_sides[$key] as $key2 => $value2){
				if(empty($master_item_type_sub_sides[$key][$key2])){
					unset($master_item_type_sub_sides[$key][$key2]);
				}
			}
		}

		$master_item_type_size = $this->excelToArray($file_path,'master_item_type_size');
		foreach ($master_item_type_size as $key => $value){
			$master_item_type_size[$key]['modified']=date('Y-m-d H:m:s');
			foreach ($master_item_type_size[$key] as $key2 => $value2){
				if(empty($master_item_type_size[$key][$key2])){
					unset($master_item_type_size[$key][$key2]);
				}
			}
		}

		$printty_products = $this->excelToArray($file_path,'printty_products');
		foreach ($printty_products as $key => $value){
			$printty_products[$key]['modified']=date('Y-m-d H:m:s');
			foreach ($printty_products[$key] as $key2 => $value2){
				if(empty($printty_products[$key][$key2])){
					unset($printty_products[$key][$key2]);
				}
			}
		}

		$printty_products_colors = $this->excelToArray($file_path,'printty_products_colors');
		foreach ($printty_products_colors as $key => $value){
			$printty_products_colors[$key]['modified']=date('Y-m-d H:m:s');
			foreach ($printty_products_colors[$key] as $key2 => $value2){
				if(empty($printty_products_colors[$key][$key2])){
					unset($printty_products_colors[$key][$key2]);
				}
			}
		}

		$printty_products_colors_sides = $this->excelToArray($file_path,'printty_products_colors_sides');
		foreach ($printty_products_colors_sides as $key => $value){
			$printty_products_colors_sides[$key]['modified']=date('Y-m-d H:m:s');
			foreach ($printty_products_colors_sides[$key] as $key2 => $value2){
				if(empty($printty_products_colors_sides[$key][$key2])){
					unset($printty_products_colors_sides[$key][$key2]);
				}
			}
		}

		$printty_products_sizes = $this->excelToArray($file_path,'printty_products_sizes');
		foreach ($printty_products_sizes as $key => $value){
			$printty_products_sizes[$key]['modified']=date('Y-m-d H:m:s');
			foreach ($printty_products_sizes[$key] as $key2 => $value2){
				if(empty($printty_products_sizes[$key][$key2])){
					unset($printty_products_sizes[$key][$key2]);
				}
			}
		}
		dd($master_item_type);
		DB::beginTransaction();

		try {

			foreach ($master_item_type as $key => $value){
				$row = DB::table('master_item_type')->where('id',$value['id'])->get();

				if(!empty($row)){

					DB::table('master_item_type')->where('id',$value['id'])->update($value);
				}else{

					DB::table('master_item_type')->where('id',$value['id'])->insert($value);
				}
			}
			var_dump('done master_item_type ===> ');
			foreach ($master_item_type_sub as $key => $value){
				$row = DB::table('master_item_type_sub')->where('id',$value['id'])->get();

				if(!empty($row)){

					DB::table('master_item_type_sub')->where('id',$value['id'])->update($value);
				}else{

					DB::table('master_item_type_sub')->where('id',$value['id'])->insert($value);
				}
			}
			var_dump('done master_item_type_sub ===> ');
			foreach ($master_item_type_sub_sides as $key => $value){
				$row = DB::table('master_item_type_sub_sides')->where('id',$value['id'])->get();

				if(!empty($row)){

					DB::table('master_item_type_sub_sides')->where('id',$value['id'])->update($value);
				}else{

					DB::table('master_item_type_sub_sides')->where('id',$value['id'])->insert($value);
				}
			}
			var_dump('done master_item_type_sub_sides ===>  ');
			foreach ($master_item_type_size as $key => $value){
				$row = DB::table('master_item_type_size')->where('id',$value['id'])->get();

				if(!empty($row)){

					DB::table('master_item_type_size')->where('id',$value['id'])->update($value);
				}else{

					DB::table('master_item_type_size')->where('id',$value['id'])->insert($value);
				}
			}
			var_dump('done master_item_type_size ===> ');

			DB::commit();
			// all good
		} catch (\Exception $e) {
			DB::rollback();
			dd('update err');
			// something went wrong
		}

		dd('done update');
	}

	public function excelToArray($filePath, $sheetName, $header=true){
		//Create excel reader after determining the file type
		$inputFileName = $filePath;
		/**  Identify the type of $inputFileName  **/
		$inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
		/**  Create a new Reader of the type that has been identified  **/
		$objReader = \PHPExcel_IOFactory::createReader($inputFileType);
		/** Set read type to read cell data onl **/
		$objReader->setReadDataOnly(true);
		/**  Load $inputFileName to a PHPExcel Object  **/
		$objPHPExcel = $objReader->load($inputFileName);
		//Get worksheet and built array with first row as header
		$objWorksheet = $objPHPExcel->getSheetByName($sheetName);
//		$objWorksheet = $objPHPExcel->getActiveSheet();
		//excel with first row header, use header as key
		if($header){
			$highestRow = $objWorksheet->getHighestRow();
			$highestColumn = $objWorksheet->getHighestColumn();
			$headingsArray = $objWorksheet->rangeToArray('A1:'.$highestColumn.'1',null, true, true, true);
			$headingsArray = $headingsArray[1];
			$r = -1;
			$namedDataArray = array();
			for ($row = 2; $row <= $highestRow; ++$row) {
				$dataRow = $objWorksheet->rangeToArray('A'.$row.':'.$highestColumn.$row,null, true, true, true);
				if ((isset($dataRow[$row]['A'])) && ($dataRow[$row]['A'] > '')) {
					++$r;
					foreach($headingsArray as $columnKey => $columnHeading) {
						$namedDataArray[$r][$columnHeading] = $dataRow[$row][$columnKey];
					}
				}
			}
		}
		else{
			//excel sheet with no header
			$namedDataArray = $objWorksheet->toArray(null,true,true,true);
		}
		return $namedDataArray;
	}
}