<?php namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetDailyPackage extends Command {

    protected $signature = "ted:daily";

    protected $description = "Gets the daily package from the TED server";

    public function handle(){

        $year = date('Y');
        $month = date('m');

        $this->line("Opening ftp connection to ted server.");
        //connect to the ftp server
        $connection = ftp_connect(env('TED_FTP'));
        $this->line("Connection established attempting to log in.");
        $login = ftp_login($connection, env('TED_USERNAME'), env('TED_PASSWORD'));

        if($login){
            $this->line("Login successful.");
            $list = ftp_nlist($connection, "./daily-packages/$year/$month/");

            $files = array_merge(app('files')->files('storage/app/ted/imported'), app('files')->files('storage/app/ted/raw'));

            foreach($list as $tar){

                $tarName = explode('/', $tar);
                $tarName = array_pop($tarName);

                // check if we already have this package
                foreach($files as $file){
                    $fileName = explode('/', $file);
                    $fileName = array_pop($fileName);
                    if(substr($fileName, 0, 8) == substr($tarName, 0, 8)){
                        continue 2;
                    }
                }

                if(!in_array('storage/app/ted', app('files')->directories('storage/app'))){
                    app('files')->makeDirectory('storage/app/ted');
                    app('files')->makeDirectory('storage/app/ted/raw');
                    app('files')->makeDirectory('storage/app/ted/imported');
                }

                // we don't have this package yet so download it.
                $this->line("Downloading $tar.");
                ftp_get($connection, storage_path('app/ted/raw/'.$tarName), $tar, FTP_BINARY);
            }
        }

        $this->line("Closing ftp connection to ted server.");

        ftp_close($connection);
    }

}