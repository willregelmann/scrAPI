<?php
namespace scrAPI;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__."/common.php";

$doc = [
    "openapi" => "3.0.0",
    "info" => [
        "title" => $_ENV["appname"] ?? "scrAPI"
    ],
    "servers" => [
        [
            "url" => sprintf(
                    "https://%s%s%s",
                    $_SERVER["HTTP_HOST"],
                    $_SERVER["CONTEXT_PREFIX"],
                    $_SERVER["REQUEST_URI"]
                )
        ]
    ],
    "paths" => [],
    "components" => [
        "schemas" => [],
    ]
];

foreach ([...scan_recursively(__DIR__."/Collections"), ...scan_recursively(__DIR__."/Schemas")] as $handle) {
    include_once ($handle);
}

$collections = array_filter(get_declared_classes(), fn($class) => preg_match('/^scrAPI\\\\Collections\\\\/', $class));
$schemas = array_filter(get_declared_classes(), fn($class) => preg_match('/^scrAPI\\\\Schemas\\\\/', $class));

foreach ($collections as $collection) {
    foreach ((new \ReflectionClass($collection))->getMethods() as $method) {
        if ($method->getAttributes(Method::class)) {
            parseMethod($method);
        }
    }
}

foreach ($schemas as $schema) {
    parseSchema(new \ReflectionClass($schema));
}

echo json_encode(
        $doc, 
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_SLASHES |
        JSON_INVALID_UTF8_SUBSTITUTE
    );



function parseSchema(\ReflectionClass $reflection) {
    global $doc;
    $doc["components"]["schemas"][$reflection->getShortName()] = [
        "type" => $reflection->getAttributes(Type::class) ? $reflection->getAttributes(Type::class)[0]->getArguments()[0] : "object",
        "properties" => [],
        "readOnly" => []
    ];
    foreach ($reflection->getProperties() as $property) {
        if ($property->getAttributes(ReadOnly::class)) {
            array_push($doc["components"]["schemas"][$reflection->getShortName()]["readOnly"], $property->getName());
        }
        $doc["components"]["schemas"][$reflection->getShortName()]["properties"][$property->getName()] = [
            "type" => $property->getType()?->getName() ?? "string"
        ];
    }
}

function parseMethod(\ReflectionMethod $method) {
    global $doc;
    preg_match(sprintf('/^%s%s(.*)\.php$/', str_replace('/', '\/', $_SERVER['DOCUMENT_ROOT']), str_replace('/', '\/', $_SERVER['REQUEST_URI'])), $method->getDeclaringClass()->getFileName(), $matches);
    $path = sprintf(
            "/%s%s",
            $matches[1],
            $method->getAttributes(Method::class) ? '/'.$method->getAttributes(Method::class)[0]->getArguments()[1] ?? "" : ""
        );
    $doc["paths"][$path] ??= [];
    $doc["paths"][$path][strtolower($method->getAttributes(Method::class)[0]->getArguments()[0])] = [
        "type" => preg_match("/^scrAPI\\\\Collections\\\\(.+)/", $method->getReturnType()?->getName(), $matches) ? 
            sprintf("#/components/schemas/%s", $matches[1]) :
            $method->getReturnType()?->getName(),
        "summary" => isset($method->getAttributes(Summary::class)[0]) ? $method->getAttributes(Summary::class)[0]?->getArguments()[0] : null
    ];
    if (isset($method->getAttributes(Description::class)[0])) {
        $doc["paths"][$path][strtolower($method->getAttributes(Method::class)[0]->getArguments()[0])]["description"] =  $method->getAttributes(Description::class)[0]?->getArguments()[0];
    }
    $doc["paths"][$path][strtolower($method->getAttributes(Method::class)[0]->getArguments()[0])]["parameters"] = parseMethodParameters(
            $method, 
            $doc["paths"][$path][strtolower($method->getAttributes(Method::class)[0]->getArguments()[0])]
        );
}

function parseMethodParameters(\ReflectionMethod $method, &$path) {
    $return = [];
    foreach ($method->getParameters() as $param) {
        $schema = preg_match("/^scrAPI\\\\Collections\\\\(.+)/", $param->getType()->getName(), $matches) ?
                ["\$ref" => sprintf("#/components/schemas/%s", $matches[1])] :
                ["type" => $param->getType()->getName()];
        if (isset($param->getAttributes(In::class)[0]) && strtolower($param->getAttributes(In::class)[0]?->getArguments()[0]) == "body") {
            $requestBody = [
                    "content" => [
                        "application/json" => [
                            "schema" => $schema
                        ]
                    ]
                ];
            $path["requestBody"] = $requestBody;
        } else {
            $new_param = [
                "name" => $param->getName(),
                "in" => match (true) {
                        $param->getAttributes(In::class) == true => strtolower($param->getAttributes(In::class)[0]->getArguments()[0]),
                        preg_match(sprintf("/\{%s\}/", $param->getName()), $method->getAttributes(Method::class)[0]?->getArguments()[1] ?? null) == true => "path",
                        default => "query"
                    },
                "required" => !$param->isOptional(),
                "schema" => $schema
            ];
            if ($param->getAttributes(Example::class)) {
                $new_param["example"] = $param->getAttributes(Example::class)[0]->getArguments()[0];
            }
            if ($param->getAttributes(Enum::class)) {
                $new_param["schema"]["enum"] = $param->getAttributes(Enum::class)[0]->getArguments();
            }
            array_push($return, $new_param);
        }
    }
    return $return;
}

function scan_recursively($directory){
    $return = [];
    $files = array_diff(scandir($directory), ["..", "."]);
    foreach ($files as $file) {
        $name = sprintf("%s/%s", $directory, $file);
        if (is_dir($file)) {
            array_push($return, ...scan_recursively($name));
        } else if (preg_match("/\.php$/", $file) && $name != $_SERVER["SCRIPT_FILENAME"]) {
            array_push($return, $name);
        }
    }
    return $return;
}
