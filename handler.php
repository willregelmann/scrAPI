<?php
namespace scrAPI;

require_once __DIR__.'/common.php';

$base_path = preg_replace('/\/handler\.php$/', '', $_SERVER['SCRIPT_NAME']);
$request_path = preg_replace(sprintf('/^%s/', str_replace('/', '\/', $base_path)), '', $_SERVER["REDIRECT_URL"]);
$request_components = array_filter(explode('/', $request_path));

$path = __DIR__.'/Collections';

while (count($request_components)) {
    $location = array_shift($request_components);
    $path .= '/'.$location;
    if (file_exists($path.'.php')) {
        $class_name = '\scrAPI\Collections\\'.$location;
        break;
    }
}

$data_type = match (true) {
        isset($_GET['csv']) => 'csv',
        isset($_GET['xml']) => 'xml',
        default => 'json'
    };

isset($class_name) ?: die(new Response(
        code: 404,
        status: "error",
        data: "requested collection not found",
        data_type: $data_type
    ));

$verb_matched = false;
foreach ((new \ReflectionClass($class_name))->getMethods() as $method) {
    $method_attributes = $method->getAttributes(Method::class);
    foreach ($method_attributes as $method_attribute) {
        $arguments = $method_attribute->getArguments();
        if ($arguments[0] !== $_SERVER['REQUEST_METHOD']) {
            continue;
        }
        $verb_matched = true;
        $pattern = isset($arguments[1]) ? preg_replace('/\{\w+\}/', '([^\/]+)', $arguments[1]) : '';
        if (preg_match('/^'.$pattern.'$/', implode('/', $request_components), $path_parameter_values)) {
            $method_name = $method->getName();
            $method_parameters = $method->getParameters();
            isset($arguments[1]) && preg_match('/\{(\w+)\}/', $arguments[1], $path_parameter_keys);
            $path_parameters = isset($arguments[1]) ? array_combine(array_slice($path_parameter_keys, 1), array_slice($path_parameter_values, 1)) : [];
            break 2;
        }
    }
}

if (!isset($method_name)) {
    [$response_code, $response_message] = $verb_matched ? [400, 'bad request'] : [501, 'method not implemented'];
    die(new Response(
        code: $response_code,
        status: 'error',
        data: $response_message,
        data_type: $data_type
    ));
}

$request_parameters = array_merge($path_parameters, $_REQUEST);
foreach ($method_parameters as $parameter) {
    $key = $parameter->getName();
    if (isset($request_parameters[$key])) {
        if (isset($class_name::$filters[$key])) {
            preg_match($class_name::$filters[$key], $request_parameters[$key]) ?: die(new Response(
                    code: 400,
                    status: 'error',
                    data: "invalid value for parameter $key",
                    data_type: $data_type
                ));
        }
        continue;
    }
    $parameter->isOptional() ?: die(new Response(
            code: 400,
            status: 'error',
            data: "$key must be provided",
            data_type: $data_type
        ));
}

array_walk($method_parameters, fn(&$param) => $param = $param->getName());

echo new Response(
        status: 'success',
        data: $class_name::$method_name(
                ...array_intersect_key(
                    $request_parameters,
                    array_flip($method_parameters)
                )
            ),
        data_type: match (true) {
            isset($_GET['csv']) => 'csv',
            isset($_GET['xml']) => 'xml',
            default => 'json'
        }
    );