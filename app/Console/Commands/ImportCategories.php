<?php namespace App\Console\Commands;

use App\Xml\Extractor;
use Illuminate\Console\Command;

class ImportCategories extends Command {

    protected $signature = "ted:categories";

    protected $description = "imports the categories list into the database.";

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
    }

}