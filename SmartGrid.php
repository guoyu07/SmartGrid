<?php

if (defined('SMART_GRID_ENABLE_AUTOLOAD') === false) {
    define('SMART_GRID_ENABLE_AUTOLOAD', true);
}

if (SMART_GRID_ENABLE_AUTOLOAD) {
    spl_autoload_register(function($class) {
        $prefixLength = strlen('SmartGrid');
        if (0 === strncmp('SmartGrid', $class, $prefixLength)) {
            $file = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, $prefixLength));
            $file = realpath(__DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.$file.'.php');
            if (file_exists($file)) {
                require_once $file;
            }
        }
    });
}

class SmartGrid extends \SmartGrid\SmartGrid
{

}
