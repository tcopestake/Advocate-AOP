<?php

spl_autoload_register(function($class)
{
    $class = str_replace('\\', '/', $class);

    $class_path = __DIR__.'/'.$class.'.php';
    
    if(file_exists($class_path)) {
        include($class_path);
    } else {
        // Try /test

        $class_path = dirname(__DIR__).'/tests/'.$class.'.php';

        if(file_exists($class_path)) {
            include($class_path);
        }
    }
    
}, true);