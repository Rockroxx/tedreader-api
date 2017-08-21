<?php namespace App\Console\Commands;

use App\Ted\Identify;
use App\Ted\Parser;
use App\Xml\Extractor;
use Illuminate\Console\Command;

class ConvertPackageToJson extends Command{

    protected $signature = "ted:convert {--package=}";

    protected $description = "Converts a raw xml package to json. Optionally use --package to specify package";

    public function handle(){

        if($this->option('package')){
            $list = [$this->option('package')];
        }
        else{
            $list = app('files')->files('storage/app/ted/raw');
        }

        $this->info("Extracting packages");
        $this->extractAndRemoveTar($list);

        $this->line("Finished extracting. Starting conversion into json.");

            $folders = app('files')->directories('storage/app/ted/raw/');
            foreach($folders as $folder){
                $folderName = explode('/', $folder);
                $folderName = array_pop($folderName);
                $this->line("Converting $folderName.");
                $files = app('files')->files($folder);
                foreach($files as $file){
                    $fileName = explode('/', $file);
                    $fileName = explode('.', array_pop($fileName))[0];
                    $x = new Extractor($file);
                    $parsed = Parser::handle($x, Identify::base($x));
                    app('files')->put("storage/app/ted/raw/$folderName/$fileName.json", json_encode($parsed));
                    unlink($file);
                }
                exec("cd ".storage_path('app/ted/raw')." && tar -czf $folderName-json.tar.gz $folderName", $output);
                if($output){
                    throw new \Exception("Error creating json tar $folderName with the following error messages.\n\n".implode("\n", $output));
                }
                array_map('unlink', glob("$folder/*.*"));
                rmdir($folder);
            }
    }

    private function extractAndRemoveTar($list, $extracted = false){
        foreach($list as $tar){
            if(strpos($tar, '.tar.gz') > 0 && strpos($tar, '-json.tar.gz') == false){
                $folderName = explode('/', $tar);
                $folderName = array_pop($folderName);
                $this->line("Extracting $folderName");
                exec("cd ".storage_path('app/ted/raw')." && tar -xf $folderName", $output);
                if($output){
                    throw new \Exception("Error extracting $tar with the following error messages.\n\n".implode("\n", $output));
                }

                $extracted = true;
                unlink($tar);
            }
        }

        if($extracted == false){
            // in case the tar.gz contains more tar.gz such as those before early 2012 we extract again
            $list = app('files')->files('storage/app/ted/raw');
            $this->extractAndRemoveTar($list, true);
        }
    }

}