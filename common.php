<?php
namespace scrAPI;

define("PATTERN_INTEGER", "/^\-?[0-9]+$/");
define("PATTERN_NUMBER", "/^\-?[0-9]*(\.[0-9]+)?$/");
define("PATTERN_ALPHA", "/^[a-z]+$/i");
define("PATTERN_ALPHA_SPACES", "/^[a-z\s]+$/i");
define("PATTERN_ALPHANUMERIC", "/^[a-z0-9]+$/i");
define("PATTERN_ALPHANUMERIC_SPACES", "/^[a-z0-9\s]+$/i");
define("PATTERN_ANY", "/^[\s\S]*$/");

spl_autoload_register(function (string $class_name):void {
    $components = array_filter(explode('\\', $class_name));
    if ($components[0] == 'scrAPI') {
        array_shift($components);
    }
    try {
        @include_once __DIR__.'/'.implode('/', $components).'.php';
    } catch (\Exception) {}
    try {
        @include_once $_SERVER['DOCUMENT_ROOT'].'/includes/'.array_pop($components).'.php';
    } catch (\Exception) {}
    
});
    
function map_properties($from, ?object &$to = null, ?string $class = null, bool $strict = false):?object {
    $to ??= new $class;
    foreach ($from as $key=>$value) {
        $property_exists = in_array($key, array_keys(
                get_class_vars(get_class($to))
            ));
        if ((!$strict || $property_exists) && $value !== null) {
            $to->{$key} = $value;
        }
    }
    return $to;
}

function bluelog($obj, string $title = null) {
    $title ??= 'BLUELOG';
    error_log(sprintf("\n\033[0;36m%s\n%s\n%s\033[0m\n",
        '----'.$title.'----',
        print_r($obj, true),
        str_repeat('-', strlen($title)+8)
    ));
}