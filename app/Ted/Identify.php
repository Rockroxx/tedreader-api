<?php namespace App\Ted;

class Identify {

    public static function base($extractor){

        return $extractor->find([

                'FORM_SECTION' => [
                    'number' => 0,
                    'element' => true
                ]

        ]);
    }

}