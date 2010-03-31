<?php // $Id$
/*
 * Copyright 2004-2010 (c) sk89q <http://www.sk89q.com>
 * All rights reserved.
*/

function autoloader($cls) {
    $inc_paths = explode(PATH_SEPARATOR, get_include_path());
    
    foreach ($inc_paths as $path) {
        $cls = str_replace("\\", "/", $cls);
        
        if (file_exists("$path/$cls.php")) {
            require_once "$path/$cls.php";
            return true;
        }
        
        $cls = str_replace("_", "/", $cls);
        
        if (file_exists("$path/$cls.php")) {
            require_once "$path/$cls.php";
            return true;
        }
    }
    
    return false;
}

spl_autoload_register('autoloader');