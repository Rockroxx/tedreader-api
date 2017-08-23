<?php namespace App\Console\Commands;

use App\Xml\Extractor;
use Illuminate\Console\Command;

class ImportCategories extends Command {

    protected $signature = "ted:categories";

    protected $description = "imports the categories list into the database and creates a tree for the SPA.";

    public function handle(){

        $xml = new Extractor('resources/cpv_2008.xml');

        $this->line("Extracting cpv codes from list.");
        $list = $xml->find(
            [
                0 => [
                    "name" => "CPV",
                    "array" => [
                        "code" => [
                            [
                                0 => ["attribute" => "CODE"]
                            ]
                        ],
                        "name" => [
                            [
                                0 => [
                                    "search" => "TEXT",
                                    "for" => "EN",
                                    "attribute" => "LANG"
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        foreach($list as $index => $item){
            $list[$index]['code'] = explode('-', $item['code'])[0];
        }

        $this->line("Inserting cpv codes into database.");
        app('db')->table('categories')->insert($list);
        $this->line("Successfully inserted ".count($list)." categories.");
        $this->line("Creating tree.");

        $this->tree($list);

        $this->line("Tree created.");
    }

    private function tree($list){
        foreach($list as $index => $item){
            $list[$index] = ["code" => rtrim($item['code'], '0'), 'name' => $item['code']];
        }
        $categories = [];

        foreach($list as $index => $item){
            if(strlen($item['code']) == 2){
                $item['children'] = $this->getChildren($item, $list);
                $categories[] = $item;
            }
        }

        app('files')->put(storage_path('categories_tree.json'), json_encode($categories));
    }

    private function getChildren($parent, $list){
        $parent_code_length = strlen($parent['code']);
        $children = [];
        foreach($list as $index => $item){
            if(strlen($item['code']) == $parent_code_length+1 && substr($item['code'], 0, $parent_code_length) == $parent['code']){
                $item['children'] = $this->getChildren($item, $list);
                $children[] = $item;
            }
        }
        return $children;
    }

}