<?php

namespace SmartGrid\base;

use SmartGrid\helpers\ValueFormatter;

class Widget
{
    public $id;

    public static function createWidget($config)
    {
        if (!empty($config['class'])) {
            $obj = new $config['class'];
            unset($config['class']);

            $obj->extendProperty($config);

//            print_r($obj);
            return $obj;
        }
    }

    private function extendProperty($property)
    {
        foreach($property as $key => $value) {
            if (is_array($value)) {
                //$this->$key = $this->advExtendProperty($this->$key, $value);
                $this->$key = ValueFormatter::extend($this->$key, $value);
                //print_r($this->{$key});
                //print_r($this->advExtendProperty($value));
            } else {
                $this->$key = $value;
            }
        }
    }

    private function advExtendProperty($obj, $name, $config)
    {
        foreach($config as $key => $value) {
            if (is_array($value)) {

            } else {
                $obj->$name[$key] = $value;
            }
        }
        return $obj->$name;
    }

    public static function widget($config = [])
    {
        ob_start();
        ob_implicit_flush(false);
        try {
            /* @var $widget Widget */
            $config['class'] = get_called_class();
            $widget = self::createWidget($config);
            $out = $widget->run();
        } catch (\Exception $e) {
            // close the output buffer opened above if it has not been closed already
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }

        return ob_get_clean() . $out;
    }
}