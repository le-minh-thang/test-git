<?php

namespace App\Http\Controllers;

use App\ProductColor;
use App\ProductColorLinkedCode;
use App\ProductColorSide;
use App\ProductColorSideSizeLinkedPlate;
use App\Product;
use App\ProductLinkedCode;
use App\ProductPrice;
use App\ProductSize;
use App\ProductUvOption;
use App\UvFrame;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

define('PLATE_XS',1);
define('PLATE_S',2);
define('PLATE_M',3);
define('PLATE_L',4);
define('PLATE_SLEEVE_LOGO',5);
//define('PLATE_S',6);

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public $tables = [
        'products' => 'App\Product',
        'products_prices' => 'App\ProductPrice',
        'products_sizes' => 'App\ProductSize',
        'products_colors' => 'App\ProductColor',
        'products_linked_codes' => 'App\ProductLinkedCode',
        'products_colors_linked_codes' => 'App\ProductColorLinkedCode',
        'products_colors_sides' => 'App\ProductColorSide',
        'products_uv_options' => 'App\ProductUvOption',
        'products_vakuum_options' => 'App\ProductVakuumOption',
        'products_colors_sides_sizes_lin' => 'App\ProductColorSideSizeLinkedPlate',
        'uv_frames' => 'App\UvFrame',
    ];

    public function import(Request $request) {
        $file_path = $this->uploadFile($request->file('file'));
        DB::beginTransaction();
        try {
//            $this->deleteWhenUpdateProduct([325,326,327,328,329,330,331,332,333,334]);

            $this->insert($file_path);

			DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            print_r($e);
            echo 'rollback';
        }

        // remove file
        unlink($file_path);
        echo "<br/>done";
    }

    public function insert($file_path){
		\Excel::load($file_path, function($reader) {
			$reader->each(function($sheet) {
				// Get table name
				$table_name = $sheet->getTitle();

				// Get field in xls file
				$header = $sheet->first()->keys()->toArray();

				// Loop through all rows
				$sheet->each(function($row) use ($header, $table_name) {
					if (($key = array_search('0', $header)) !== false) {
						unset($header[$key]);
					}

					//Get model name and ini new Object
					$table = $this->tables[$table_name];
					$model = new $table();

					// check is products_colors_sides table
					if ($table_name == 'products_colors_sides') {
						$imageURL = 'https://s3-ap-northeast-1.amazonaws.com/storage.up-t.jp/Products/fullsize/' . $row->image;
						if(!empty($row->image_width)) {
							$content = '
                                    {  "id": "' . $row->number_side . '",
                                    "imageUrl": "'. $imageURL .'",
                                    "size": {    "cm": {    "width": ' . $row->image_width . ',    "height": ' . $row->image_height . '    },    "pixel": {    "width": ' . $row->image_width . ',    "height": ' . $row->image_height . '    }  },
                                    "canvas": {    "objects": [],    "background": "#ffffff",    "backgroundImage": {      "type": "image",      "originX": "center",      "originY": "center",      "left": 0,      "top": 0,
                                    "width": ' . $row->image_width . ',    "height": ' . $row->image_height . ',      "fill": "rgb(0,0,0)",      "stroke": null,      "strokeWidth": 0,      "strokeDashArray": null,      "strokeLineCap": "butt",
                                    "strokeLineJoin": "miter", "strokeMiterLimit": 10,      "scaleX": 0.73,      "scaleY": 0.73,      "angle": 0,      "flipX": false,      "flipY": false,      "opacity": 0.5,      "shadow": null,      "visible": true,
                                    "clipTo": null,      "backgroundColor": "",      "fillRule": "nonzero",      "globalCompositeOperation": "source-over",      "transformMatrix": null,
                                    "skewX": 0,      "skewY": 0,      "uuid": "7d55846954a24821a426b83a858bd5b9",
                                    "src": "'. $imageURL .'",
                                    "filters": [],      "resizeFilters": [],      "crossOrigin": "anonymous",      "alignX": "none",      "alignY": "none",      "meetOrSlice": "meet"    }  },
                                    "border": {      "cm": {    "left": ' . $row->left . ',    "top": ' . $row->top . ',    "width": ' . $row->width . ',    "height": ' . $row->height . '      },
                                    "pixel": {    "left": ' . $row->left . ',    "top": ' . $row->top . ',    "width": ' . $row->height . ',    "height": ' . $row->height . '      }  }}
                                ';
						} else {
							$content = '{}';
							//$imageURL = '';
						}

						$model->color_id = $row->color_id;
						$model->title = $row->title;
						$model->small_image_url = $imageURL;
						$model->medium_image_url = $imageURL;
						$model->image_url = $imageURL;
						$model->content =  preg_replace('/\s+/', '', $content);
						$model->is_main = $row->is_main;
						$model->is_deleted = $row->is_deleted;

					} else {
						foreach ($header as $field) {
							$model->{$field} = $row->{$field};
						}
					}

					// Save data into database
					$model->save();

				});
			});
		});
		echo '<br/>^O^ .... Success!!!!';
		return true;
	}

    /*
     * Save linked plate
     * */
    public function saveLinkedPlate() {
        $productIds = [329,330,331,332,333,334];

        DB::beginTransaction();
        try {
            foreach ($productIds as $id) {
                $sqlSides = "SELECT pcs.* FROM products_colors_sides as pcs
                    JOIN products_colors as pc ON pc.id = pcs.color_id
                    where pc.product_id in ($id)";

                // GET SIDES
                $sides = DB::select($sqlSides);

                $sqlSizes = "SELECT * from products_sizes WHERE product_id in ($id)";

                // GET SIZES
                $sizes = DB::select($sqlSizes);

                foreach ($sides as $side) {
					$sideTitle = $side->title;

					if ($sideTitle == '表' || $sideTitle == '裏') {
						foreach ($sizes as $size) {
							$title = $size->title;

							if (in_array($title, ['XS'])) { // S
								$plate = PLATE_S;
							} else if (in_array($title, ['S', 'M', 'L'])) { // M
								$plate = PLATE_M;
							} else if (in_array($title, ['XL', 'XXL', 'XXXL', 'XXXL'])) { // L
								$plate = PLATE_L;
							}

							$plateModel = new ProductColorSideSizeLinkedPlate();
							$plateModel->side_id = $side->id;
							$plateModel->size_id = $size->id;
							$plateModel->plate_id = $plate;
							$plateModel->is_main = 0;
							$plateModel->is_deleted = 0;
							$plateModel->save();
						}
					}

					if ($sideTitle == '左袖' || $sideTitle == '右袖') {
						foreach ($sizes as $size) {
							$plate = PLATE_SLEEVE_LOGO;

							$plateModel = new ProductColorSideSizeLinkedPlate();
							$plateModel->side_id = $side->id;
							$plateModel->size_id = $size->id;
							$plateModel->plate_id = $plate;
							$plateModel->is_main = 0;
							$plateModel->is_deleted = 0;
							$plateModel->save();
						}
					}
                }
            }

            DB::commit();
            echo '^O^ .... Plate success!!!!';

        } catch (\Exception $e) {
            DB::rollback();
            print_r($e);
			echo 'rollback';
        }
    }


    /**
     * @param $file
     * @return string
     */
    public function uploadFile($file) {
        $name = 'data-import.' . $file->getClientOriginalExtension();
        $destinationPath = public_path('\data');
        $file->move($destinationPath, $name);
        return public_path('data/') . $name;
    }

    public function deleteWhenUpdateProduct($array){
        $arr = [];
        $pcs = ProductColor::whereIn('product_id',$array)->get();
        foreach($pcs AS $pc){
            $arr[] = $pc->id;
        }

        foreach($array AS $id){
            Product::where('id',$id)->delete();
            ProductPrice::where('product_id',$id)->delete();
            ProductSize::where('product_id',$id)->delete();
            ProductColor::where('product_id',$id)->delete();
            ProductLinkedCode::where('product_id',$id)->delete();

            //delete linked plate
            $sqlSides = "SELECT pcs.id FROM products_colors_sides as pcs
                JOIN products_colors as pc ON pc.id = pcs.color_id
                where pc.product_id in ($id)";
            // GET SIDES
            $sides = DB::select($sqlSides);
            ProductColorSideSizeLinkedPlate::whereIn('side_id',$sides)->delete();
            ProductUvOption::where('product_id',$id)->delete();
        }
        ProductColorSide::whereIn('color_id',$arr)->delete();
        ProductColorLinkedCode::whereIn('product_color_id',$arr)->delete();

		UvFrame::whereIn('id',array(24,25,26,27))->delete();
		echo 'delete complete';
    }
}
