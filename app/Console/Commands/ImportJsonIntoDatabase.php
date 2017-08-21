<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportJsonIntoDatabase extends Command{

    protected $description = "Extracts an package and imports the contents into the database. Optionally use --package to specify package.";

    protected $signature = "ted:import {--package=}";

    public function handle(){

        $allCategories = app('db')->table('categories')->select('id', 'code')->get();

        $keys = array_pluck($allCategories, 'id');
        $codes = array_pluck($allCategories, 'code');
        $allCategories = array_combine($codes, $keys);

        if($this->option('package')){
            $list = [$this->option('package')];
        }
        else{
            $list = app('files')->files('storage/app/ted/raw');
        }

        foreach($list as $tar){
            if(strpos($tar, '-json.tar.gz') > 0){
                $tarName = explode('/', $tar);
                $tarName = array_pop($tarName);
                $this->line("Extracting $tar.");
                exec("cd ".storage_path('app/ted/raw')." && tar -xf $tarName", $output);
                if($output){
                    throw new \Exception("Error extracting $tar with the following error messages.\n\n".implode("\n", $output));
                }

                $folders = app('files')->directories('storage/app/ted/raw/');
                foreach($folders as $folder){
                    $folderName = explode('/', $folder);
                    $folderName = $folderName[count($folderName)-1];

                    $files = app('files')->files($folder);

                    $this->info("Importing $folder into database.");
                    $this->output->progressStart(count($files));
                    foreach($files as $file){
                        $data = json_decode(app('files')->get($file), true);
                        $db = app('db');
                        try {
                            // TODO save the references/file locations for later manual inspection
                            if(!isset($data['title'], $data['description']) || in_array($data['identity'], ["EEIG", "OTH_NOT", "F08_2014", "F14_2014", "F20_2014", "BUYER_PROFILE"]))
                                continue;

                            app('db')->beginTransaction();

                            $slug = Str::slug(mb_strimwidth($data['title'] ?? $data['description'], 0, 100, '').'-'.Str::random(5));
                            $notice_id = $db->table('notices')->insertGetId([
                                "reference" => $data['ref'],
                                "referenced" => $data['referenced'] ?? null,
                                "lang" => $data['lang'],
                                "type" => $data['type'],
                                "nature" => $data['nature'],
                                "published" => $data['published_at'],
                                "value" => $data['value'] ?? null,
                                "currency" => $data['currency'] ?? null,
                                "deadline" => $data['awarded_at'] ?? null,
                                "slug" => $slug
                            ]);
                            $categories = $data['categories'] ?? [];
                            $cat_ids = [];
                            foreach($categories as $category){
                                foreach($allCategories as $a_category){
                                    if($a_category['code'] === $category)
                                        $cat_ids[] = ['notice_id' => $notice_id, 'category_id' => $a_category['id']];
                                }
                            }
                            if(count($cat_ids))
                                $db->insert($cat_ids);

                            $lots = $data['lot'] ?? [];
                            foreach($lots as $lot){
                                if(!isset($lot['title'], $lot['description'], $lot['duration'], $lot['duration_type'], $lot['value'], $lot['currency']))
                                    continue;
                                $db->table('notice_lots')->insert([
                                    "notice_id" => $notice_id,
                                    "title" => $lot['title'] ?? null,
                                    "description" => $lot['description'] ?? null,
                                    "duration" => $lot['duration'] ?? null,
                                    "duration_type" => strtolower($lot['duration_type'] ?? ""),
                                    "value" => $lot['value'] ?? null,
                                    "currency" => $lot['currency'] ?? null
                                ]);

                            }
                            $contacts = $data['contacts'] ?? [];
                            foreach($contacts as $contact){
                                $db->table('notice_contacts')->insert([
                                    'notice_id' => $notice_id,
                                    "type" => $contact['type'],
                                    "official_name" => $contact['official_name'] ?? null,
                                    "name" => $contact['name'] ?? null,
                                    "country" => $contact['country'] ?? null,
                                    "city" => $contact['city'] ?? null,
                                    "street" => $contact['street'] ?? null,
                                    "postal" => $contact['postal'] ?? null,
                                    "email" => $contact['email'] ?? null,
                                    "phone" => $contact['phone'] ?? null,
                                    "fax" => $contact['fax'] ?? null
                                ]);
                            }
                            $db->table('notice_details')->insert([
                                'notice_id' => $notice_id,
                                "title" => $data['title'],
                                "description" => $data['description'],
                                "document_url" => $data['document_url'],
                                "body_url" => $data['body_url'],
                                "tendering_url" => $data['tendering_url'] ?? null,
                                "body" => $data['body'],

                            ]);

                            $awards = $data['award'] ?? [];
                            foreach($awards as $award){
                                if(!isset($award['awarded_at'], $award['value'], $award['currency'], $award['contractor']))
                                    continue;
                                if(isset($award['awarded_at']) && is_array($award['awarded_at']))
                                    $award['awarded_at'] = $award['awarded_at']['year'].'-'.$award['awarded_at']['month'].'-'.$award['awarded_at']['day'];
                                $db->table('notice_awards')->insert([
                                    "notice_id" => $notice_id,
                                    "lot" => $award['lot'] ?? null,
                                    'awarded_at' => $award['awarded_at'] ?? null,
                                    "value" => $award['value'] ?? null,
                                    "currency" => $award['currency'] ?? null
                                ]);
                                $db->table('notice_contacts')->insert([
                                    "notice_id" => $notice_id,
                                    "type" => "t",
                                    "official_name" => $award['contractor']['official_name'],
                                    "name" => $award['contractor']['name'] ?? null,
                                    "country" => $award['contractor']['country'] ?? null,
                                    "city" => $award['contractor']['city'] ?? null,
                                    "street" => $award['contractor']['street'] ?? null,
                                    "postal" => $award['contractor']['postal'] ?? null,
                                    "email" => $award['contractor']['email'] ?? null,
                                    "phone" => $award['contractor']['phone'] ?? null,
                                    "fax" => $award['contractor']['fax'] ?? null
                                ]);
                            }

                            if(isset($data['category'])){
                                $ids = [];
                                foreach($data['category'] as $category){
                                    $ids = ['notice_id' => $notice_id, 'category_id' => $allCategories[$category]];
                                }
                                app('db')->table('notice_categories')->insert($ids);
                            }

                            app('db')->commit();

                        }
                        catch (\Exception $e){
                            // Insert failed somewhere
                            // TODO import failed for this file. Save reference/file location for later manual inspection
                            $db->rollback();
                            throw($e);
                        }
                        $this->output->progressAdvance();
                    }

                    $this->output->progressFinish();

                    $first = explode('/',$files[0]);
                    $first = explode('_', explode('.', $first[count($first)-1])[0]);
                    $year= $first[1];
                    $first = $first[0];

                    $last = explode('/', $files[count($files)-1]);
                    $last = explode('_', explode('.', $last[count($last)-1])[0])[0];

                    app('files')->move($tar, storage_path("app/ted/imported/$tarName"));

                    $db->table('tar_locations')->insert([
                        'tar' => "app/ted/imported/$tarName",
                        'location' => $folderName,
                        'start' => $first,
                        'end' => $last,
                        'year' => $year
                    ]);

                    array_map('unlink', glob("$folder/*.*"));
                    rmdir($folder);
                }
            }
        }

    }

}