<?php
namespace scrAPI\Collections;
use scrAPI\Schemas\Sample;
use scrAPI\{
    Method,
    Summary
};

include_once __DIR__."/../common.php";

final class Samples {

    public static $filters = [
            'color' => '/^[a-z]$/i'
        ];
    
    #[
        Method("GET"),
        Summary("List all samples"),
    ]
    public static function list():array {
        $samples = json_decode(file_get_contents(__DIR__."/Samples.json"));
        array_walk($samples, fn(&$sample) => $sample = @\scrAPI\map_properties($sample, new Sample));
        return $samples;
    }
    
    #[
        Method("GET", "{id}"),
        Summary("Retrieve a sample by ID")
    ]
    public static function read(int $id):?Sample {
        $samples = json_decode(file_get_contents(__DIR__."/Samples.json"));
        $matches = array_values(array_filter($samples, fn($sample) => $sample->id == $id));
        return match (count($matches) <=> 1) {
            -1 => !http_response_code(404) ?: null,
            0 => @\scrAPI\map_properties($matches[0], new Sample),
            1 => !http_response_code(500) ?: null
        };
    }
        
    #[
        Method("PUT", "{id}"),
        Summary("Update a sample")
    ]
    public static function update(int $id, #[In("body")] ?Sample $device = null):?object {
        return self::read($id);
    }
    
    #[
        Method("DELETE", "{id}"),
        Summary("Delete a sample")
    ]
    public static function delete(int $id):bool {
        return false;
    }

}