<?php

namespace SmartGrid\helpers;

class JavaScript
{

    public static function encode($value, $safe = false)
    {
        if (is_string($value))
        {
            if (strpos($value, 'js:') === 0 && $safe === false) {
                return substr($value, 3);
            } else {
                return "'". self::quote($value) ."'";
            }
        } else if ($value === null) {
            return 'null';
        } else if (is_bool($value)) {
            return $value ? 'true' : 'false';
        } else if (is_integer($value)) {
            return $value;
        } else if (is_float($value)) {
            if ($value === -INF) {
                return 'Number.NEGATIVE_INFINITY';
            } else if ($value === INF) {
                return 'Number.POSITIVE_INFINITY';
            } else {
                return rtrim(sprintf('%.8F',$value),'0');
            }
        } else if (is_object($value)) {
            return self::encode(get_object_vars($value), $safe);
        } else if (is_array($value)) {
            $arr = array();
            if (($n = count($value)) > 0 && array_keys($value) !== range(0, $n-1)) {
                foreach ($value as $key => $value) {
                    $arr[] = self::quote($key) .":". self::encode($value, $safe);
                }
                return '{'. implode(',', $arr) .'}';
            } else {
                foreach ($value as $key => $value) {
                    $arr[] = self::encode($value, $safe);
                }
                return '['. implode(',', $arr) .']';
            }
        } else {
            return '';
        }
    }

    public static function quote($js, $url = false)
    {
        if ($url === true) {
            return strtr($js,array('%'=>'%25',"\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\','</'=>'<\/'));
        } else {
            return strtr($js,array("\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\','</'=>'<\/'));
        }
    }

}