<?php
namespace wregelmann\EzAPI;
    
spl_autoload_register(function (string $class_name) {

    if (preg_match("/^(.*)\\\\([^\\\\]+)$/i", $class_name, $matches)) {

        $class_shortname = $matches[2];

        $path = null;
        foreach (array_diff(scandir(__DIR__."/../api"), [".",".."]) as $file) {
            if ($file == strtolower($class_shortname).".php") {
                $path = "../api";
                break;
            } else if (is_dir(__DIR__."/../api/$file")) {
                foreach (array_diff(scandir(__DIR__."/../api/".$file), [".",".."]) as $file_sub) {
                    if ($file_sub == strtolower($class_shortname).".php") {
                        $path = "../api/$file";
                        break;
                    }
                }
                if ($path !== null) break;
            }
        }
        if ($path == null) return;

        try {
            include_once sprintf(
                    "%s/%s/%s.php",
                    __DIR__, 
                    $path,
                    strtolower($class_shortname)
                );
        } catch (\Exception) {}

    }
});
    

    
function map($from, object &$to, bool $strict = false):?object {

    foreach ($from as $key=>$value) {

        $property_exists = in_array(
                $key ,
                array_keys(
                    get_class_vars(
                        get_class($to)
                    )
                )
            );

        if ((!$strict || $property_exists) && $value !== null) {
            $to->{$key} = $value;
        }

    }

    return $to;

}