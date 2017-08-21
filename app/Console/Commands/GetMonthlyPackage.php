<?php namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetMonthlyPackage extends Command {

    protected $signature = "ted:monthly {--year=}";

    protected $description = "Gets the monthly packages from the TED server. Default current year. Specify year with --year.";

    public function handle(){

        $year = $this->option("year") ?? date('Y');

        $this->line("Opening ftp connection to ted server.");
        //connect to the ftp server
        $connection = ftp_connect(env('TED_FTP'));
        $this->line("Connection established attempting to log in.");
        $login = ftp_login($connection, env('TED_USERNAME'), env('TED_PASSWORD'));

        if($login){
            $this->line("Login successful.");
            $list = ftp_nlist($connection, "./monthly-packages/$year/");

            $files = array_merge(app('files')->files('storage/app/ted/imported'), app('files')->files('storage/app/ted/raw'));
            foreach($list as $tar){
                $tarName = explode('/', $tar);
                $tarName = array_pop($tarName);
                $firstSix = substr(str_replace('-', '', $tarName), 0, 6);
                foreach($files as $file){
                    $fileName = explode('/', $file);
                    $fileName = array_pop($fileName);
                    if(substr($fileName, 0, 6) == $firstSix || substr(str_replace('-', '', $fileName), 0, 6) == $firstSix)
                        continue 2;
                }

                if(!in_array('storage/app/ted', app('files')->directories('storage/app'))){
                    app('files')->makeDirectory('storage/app/ted');
                    app('files')->makeDirectory('storage/app/ted/raw');
                    app('files')->makeDirectory('storage/app/ted/imported');
                }

                $this->line("Downloading $tarName.");
                ftp_get($connection, storage_path("app/ted/raw/".$tarName), $tar, FTP_BINARY);
            }
        }

        $this->line("Closing ftp connection to ted server.");

        ftp_close($connection);
    }

}