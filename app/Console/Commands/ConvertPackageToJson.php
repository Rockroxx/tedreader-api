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

        $this->info("Extracting and converting packages");
        $this->extractAndRemoveTar($list);

        // We do this twice in case there are tars inside tars.
        $list = app('files')->files('storage/app/ted/raw');
        $this->extractAndRemoveTar($list);

        $this->line("Finished extracting. Starting conversion into json.");

    }

    private function extractAndRemoveTar($list){
        foreach($list as $tar){
            if(strpos($tar, '.tar.gz') > 0 && strpos($tar, '-json.tar') == false){
                $folderName = explode('/', $tar);
                $folderName = array_pop($folderName);
                $this->line("Extracting $folderName");
                exec("cd ".storage_path('app/ted/raw')." && tar -xf $folderName", $output);
                if($output){
                    throw new \Exception("Error extracting $tar with the following error messages.\n\n".implode("\n", $output));
                }
                unlink($tar);
                $this->convert(str_replace('.tar.gz', '-json.tar', $tar));
            }
        }

    }

    private function convert($tar){
        $folders = app('files')->directories('storage/app/ted/raw/');
        exec("tar cfT $tar /dev/null", $output);
        if($output){
            throw new \Exception("Error creating empty tar archive $tar with the following error messages.\n\n".implode("\n", $output));
        }
        foreach($folders as $folder){
            $folderName = explode('/', $folder);
            $folderName = array_pop($folderName);
            $this->line("Converting $folderName.");
            $files = app('files')->files($folder);
            foreach($files as $file){
                if(strpos($file, '.xml') == false)
                    continue;
                $fileName = explode('/', $file);
                $fileName = explode('.', array_pop($fileName))[0];
                $x = new Extractor($file);
                $parsed = Parser::handle($x, Identify::base($x));
                app('files')->put("storage/app/ted/raw/$folderName/$fileName.json", json_encode($parsed));
                unlink($file);
            }
            exec("cd ".storage_path('app/ted/raw')." && tar -rf $tar $folderName", $output);
            if($output){
                throw new \Exception("Error adding folder $folderName to tar $tar with the following error messages.\n\n".implode("\n", $output));
            }
            array_map('unlink', glob("$folder/*.*"));
            rmdir($folder);
        }
        exec("gzip $tar", $output);
        if($output){
            throw new \Exception("Error compressing $tar with the following error messages.\n\n".implode("\n", $output));
        }
    }

}