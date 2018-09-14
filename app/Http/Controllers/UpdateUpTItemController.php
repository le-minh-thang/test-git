<?php

namespace App\Http\Controllers;

class UpdateUpTItemController extends Controller
{
    private $_ids               = [];
    private $_pageNames         = [];
    private $_otherPageNames    = [];
    private $_notFoundPageNames = [];

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update()
    {
        set_time_limit(1000);
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 1000);

        echo '<pr>';

        // these files below updated the content add to the BD
        $pcNobodyItemContent    = file_get_contents(resource_path('items/pc-nobody-item-detail.html'));
        $smartNobodyItemContent = file_get_contents(resource_path('items/smart-nobody-item-detail.html'));

        preg_match_all("/<a href=\"\/page\.php\?p=([\w|\d|_|-]*)[\"|\S]?/i", $pcNobodyItemContent, $pcItemLinks);
        preg_match_all("/<a href=\"\/page\.php\?p=([\w|\d|_|-]*)[\"|\S]?/i", $smartNobodyItemContent, $smartItemLinks);

        $this->_getItemPageNames(array_merge($pcItemLinks[1], $smartItemLinks[1]));

        // path to directory to scan
        $directory = 'D:\xampp\htdocs\up-t-web\template\pc\html\page\nobody\\';
        $this->replaceItemContents($directory, 'pc');
        $directory = 'D:\xampp\htdocs\up-t-web\template\smart\html\page\nobody\\';
        $this->replaceItemContents($directory, 'smart');

        // create sql string when count page names < 1000
        $sqlUpdateItems = $this->_getSqlString();

        echo '<br />';
        var_dump($sqlUpdateItems);
        echo '<br />';
        var_dump('not found page name');
        echo '<br />';
        dd($this->_pageNames);
        dd($this->_notFoundPageNames);
        echo '</pr>';
    }

    /**
     * Get Item Page Name
     *
     * @param $itemLinks
     */
    private function _getItemPageNames($itemLinks)
    {
        foreach ($itemLinks as $itemLink) {
            $this->_pageNames[$itemLink] = $itemLink;
        }
    }

    /**
     * Create sql string to insert into DB
     *
     * @return string
     */
    private function _getSqlString()
    {
        $i         = 1;
        $sql       = 'UPDATE master_item_type SET page_name = CASE id';
        $stringIds = '';
        foreach ($this->_pageNames as $pageName) {
            $link = sprintf('https://up-t.jp/page.php?p=%s', $pageName);

            try {
                $pageContent = file_get_contents($link);

                preg_match('/model_id=([\w\d]*)/i', $pageContent, $ids);

                if (count($ids) == 2) {
                    var_dump('get content of item ' . $ids[1]);
                    $this->_ids[] = $ids[1];
                    $sql          .= sprintf(" WHEN '%s' THEN '%s'", $ids[1], $pageName);
                    if ($i == 1) {
                        $stringIds = sprintf("'%s'", $ids[1]);
                    } else {
                        $stringIds .= sprintf(", '%s'", $ids[1]);
                    }
                } else {
                    $this->_notFoundPageNames['not sql ' . $pageName] = $link;
                }
            } catch (\Exception $exception) {
                $this->_notFoundPageNames['not sql ' . $pageName] = $link;
            }

            $i++;
        }

        $sql .= ' ELSE NULL';
        $sql .= ' END';
        $sql .= sprintf(' WHERE id IN (%s)', $stringIds);

        return $sql;
    }

    /**
     * Replace Item Content
     *
     * @param $directory
     * @param $location
     */
    public function replaceItemContents($directory, $location)
    {
        // get all files in specified directory
        $directories = glob($directory . "*");

        // print each file name
        foreach ($directories as $directory) {
            //check to see if the file is a folder/directory
            if (is_dir($directory)) {
                // get all files in specified directory
                $files = glob($directory . "\*");
                foreach ($files as $file) {
                    //check to see if the file is a folder/directory
                    if (is_file($file)) {
                        $paths    = explode('\\', str_replace('.html', '', $file));
                        $fileName = $paths[count($paths) - 1];

                        if (count(explode('.png', $fileName)) == 1) {
                            $this->_pageNames[$fileName] = $fileName;
                        }

                        $fileContent = file_get_contents($file);

                        preg_match_all("/<li><strong>(.*)<\/strong><\/li>/mi", $fileContent, $itemNames);

                        if (count($itemNames) >= 2) {
                            if (isset($itemNames[1][0])) {
                                $information = '<ul class="clearfix"><li class="home"><a href="https://up-t.jp/">Up-T TOP</a></li>';
                                $information .= "<!--# draw showItemInformation {$fileName} 商品と価格一覧 {$itemNames[1][0]} #-->";

                                $information .= '</ul>';
                                $number      = 2;
                                for ($j = 16; $j >= 1; $j--) {
                                    preg_match_all("/<ul class=\"clearfix\">(.*\n.*){1,$j}<\/ul>/mi", $fileContent, $result);

                                    if (isset($result[0][0])) {
                                        var_dump($j);
                                        $number = $j;
                                        break;
                                    }
                                }

                                $fileContent = preg_replace("/<ul class=\"clearfix\">(.*\n.*){1,$number}<\/ul>/", $information, $fileContent);

                                if (!empty($fileContent)) {
                                    file_put_contents($file, $fileContent);
                                } else {
                                    $this->_notFoundPageNames[$fileName . ' file ' . $location] = $file;
                                }
                            } else {
                                $this->_notFoundPageNames[$fileName . ' file ' . $location] = $file;
                            }
                        } else {
                            $this->_notFoundPageNames[$fileName . ' file ' . $location] = $file;
                        }
                        if (isset($this->_pageNames[$fileName])) {

                        } else {
//                            $this->_otherPageNames[$fileName] = $fileName;
                            $this->_notFoundPageNames[ ' file not found ' . $fileName . $location] = $file;
                        }
                    }
                }
            }
        }
    }
}