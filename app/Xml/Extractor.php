<?php namespace App\Xml;

class Extractor{

    private $raw;

    public function __construct($file_path)
    {
        $this->raw = simplexml_load_string(app('files')->get($file_path), 'SimpleXMLElement', LIBXML_NOWARNING);
    }

    public function multiple($options){
        foreach($options as $layout){
            $result = $this->find($layout);
            if($result)
            {
                return $result;
            }
        }
    }

    public function find($layout, $raw = false){
        if($raw != false) $current_element = $raw;
        else
            $current_element = $this->raw;

        foreach($layout as $location => $options){


            if(is_numeric($location) && !is_array($options)){
                $location = $options;
                $options = null;
            }

            if(!is_numeric($location)){
                $current_name = $current_element->getName();
                foreach($current_element->children() as $child){
                    if($child->getName() == $location){
                        $current_element = $child;
                        break;
                    }
                }
                if($current_element->getName() == $current_name)
                    return null;

            }

            if($options != null){
                $current_element = $this->options($current_element, $options);
            }
        }

        if($current_element instanceof \SimpleXMLElement){
            if(count($current_element->children()) === 0)
                return (string) $current_element;
            return null;
        }
        else{
            return $current_element;
        }
    }

    private function options($element, $options){
        if(isset($options['search'])){
            $search = $options['search'];
            unset($options['search']);
            foreach($element->children() as $child){
                if($child->getName() == $search){
                    $extracted = $this->options($child, $options);
                    if($extracted instanceof \SimpleXMLElement){
                        $extracted = (string) $extracted;
                    }
                    if($extracted == $options['for']){
                        return $child;
                    }
                }
            }
        }

        if(isset($options['array'])){

            $arr = [];

            foreach($element->children() as $child){

                if($child->getName() == $options['name']){
                    $current_array = [];

                    foreach($options['array'] as $name => $possibility){
                        foreach($possibility as $layout){
                            if($e = $this->find($layout, $child)){
                                $current_array[$name] = $e;
                            }
                        }
                    }

                    if(count($current_array) > 0)
                        $arr[] = $current_array;
                }
            }

            return $arr;
        }

        if(isset($options['multiple'])){
            $arr = [];

            foreach($options['multiple'] as $name => $possibility){
                foreach($possibility as $layout){
                    if($e = $this->find($layout, $element)){
                        $arr[$name] = $e;
                        continue 2;
                    }
                }
            }

            return $arr;
        }

        if(isset($options['attribute'])){
            if(isset($element[$options['attribute']]))
                return $element[$options['attribute']];
        }

        if(isset($options['raw'])){
            return $element;
        }

        if(isset($options['xml'])){
            return $element->asXML();
        }

        if(isset($options['number'])){
            if(isset($element->children()[$options['number']])){
                $element = $element->children()[$options['number']];
            }
        }

        if(isset($options['element'])){
            $element = $element->getName();
        }

        return $element;
    }



}