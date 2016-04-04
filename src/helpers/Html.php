<?php

namespace SmartGrid\helpers;

class Html
{
    public static function fontAwesome($class) 
    {
        return self::tag('i', ['class' => $class], '');
    }

    public static function tag($tag, $htmlOptions = [], $content = false, $closeTag = true)
    {
        $html = '<'. $tag . self::parseAttributes($htmlOptions);
        if ($content !== false) {
            $html .= $closeTag ? '>'. $content . self::closeTag($tag) : '>'. $content;
        } else {
            $html .= $closeTag ? '/>' : '>';
        }
        return $html;
    }

    public static function openTag($tag, $htmlOptions = [])
    {
        return self::tag($tag, $htmlOptions, false, false);
    }

    public static function closeTag($tag)
    {
        return '</'. $tag .'>';
    }

    public static function addCssClass($class, array &$htmlOptions)
    {
        self::addAttribute('class', $class, $htmlOptions);
    }

    public static function addCssId($id, array &$htmlOptions)
    {
        self::addAttribute('id', $id, $htmlOptions);
    }

    public static function addAttribute($attr, $value, array &$htmlOptions)
    {
        if ($attr && $value) {
            if (isset($htmlOptions[$attr])) {
                $htmlOptions[$attr] .= ' '. $value;
            } else {
                $htmlOptions[$attr] = $value;
            }
        }
    }

    public static function removeAttribute($attr, $value, array &$htmlOptions) {
        if (isset($htmlOptions)) {
            if ($value === null) {
                unset($htmlOptions[$attr]);
            } else {
                $string = $htmlOptions[$attr];
                $wordlist = [$value];
                foreach ($wordlist as &$word) {
                    $word = '/\b' . preg_quote($word, '/') . '\b/';
                }
                $string = preg_replace($wordlist, '', $string);
                $htmlOptions[$attr] = $string;
            }
        }
    }

    public static function getValue(array &$array, $attr, $null = '', $unset = true)
    {
        $value = isset($array[$attr]) ? $array[$attr] : $null;
        if($unset === true) {
            self::removeAttribute($attr, null, $array);
        }
        return $value;
    }

    protected static function parseAttributes($htmlOptions = [])
    {
        $attr = '';
        foreach ($htmlOptions as $key => $value) {
            if (is_bool($value)) {
                $attr .= ' '. $key;
            } else {
                $attr .= ' '. $key .'="'. $value .'"';
            }
        }
        return $attr;
    }
}