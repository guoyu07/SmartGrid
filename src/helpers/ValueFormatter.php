<?php

namespace SmartGrid\helpers;

class ValueFormatter
{
    
    public static function extend()
    {
        $args = func_get_args();
        while ($extended = array_shift($args)) {
            if (is_array($extended)) {
                break;
            }
        }

        if (!is_array($extended)) {
            return FALSE;
        }

        while ($array = array_shift($args)) {
            
            if (is_array($array)) {
                foreach ($array as $key => $value) {
//                    $extended[$key] = is_array($value) && isset($extended[$key]) ? self::extend(is_array($extended[$key]) ? $extended[$key] : array(), $value) : $value;
                    
                    //$extended[$key] = $value;
                    if (is_array($value) && isset($extended[$key])) {
                        $extended[$key] = self::extend(is_array($extended[$key]) ? $extended[$key] : array(), $value);
                    } else {
                        $extended[$key] = $value;
                    }
                }
            }
        }
        return $extended;
    }
    
}