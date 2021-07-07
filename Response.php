<?php
namespace scrAPI;

class Response {
        
    public function __construct(
        public string $status,
        public mixed $data,
        public string $data_type = 'json',
        public ?int $code = null
    ) {
        if (isset($code)) {
            http_response_code($code);
        }
    }
    
    public function __toString() {
        return match ($this->data_type) {
            'xml' => $this->to_xml_document(),
            'csv' => $this->to_csv(),
            default => $this->to_json()
        };
    }
    
    private function to_xml(string $node_name, mixed $data = null):string {
        $xml = '';
        if (is_array($data) || is_object($data)) {
            foreach ($data as $key=>$value) {
                if (is_numeric($key)) {
                    $key = $node_name;
                }

                $xml .= sprintf('<%s>%s</%s>', $key, $this->to_xml($node_name, $value), $key);
            }
        } else {
            $xml = htmlspecialchars($data, ENT_QUOTES);
        }
        return $xml;
    }

    function to_xml_document() {
        $obj = ['status' => $this->status];
        if (is_array($this->data) && is_numeric(array_key_first($this->data))) {
            $obj = array_push($obj, ...$this->data);
        } else {
            $obj['data'] = $this->data;
        }
        return '<?xml version="1.0" encoding="UTF-8" ?>'.$this->to_xml('data', ['response' => $obj]);
    }
    
    private function to_csv():string {
        $f = tmpfile();
        $assoc = json_decode(json_encode(is_array($this->data) ? $this->data : [$this->data]), true);
        $keys = [];
        foreach ($assoc as $row) {
            array_push($keys, ...array_keys($row));
        }
        fputcsv($f, array_unique($keys));
        $template = array_map(function(){
                return null;
            }, array_flip(array_unique($keys)));
        foreach ($assoc as $row) {
            fputcsv($f, array_merge($template, $row));
        }
        return file_get_contents(stream_get_meta_data($f)['uri']);
    }
    
    private function to_json():string {
        return json_encode(
                (object)[
                    "status" => $this->status,
                    "data" => $this->data
                ],
                array_reduce([
                    JSON_PRETTY_PRINT,
                    JSON_INVALID_UTF8_SUBSTITUTE,
                    JSON_UNESCAPED_SLASHES
                ], fn($a,$b) => $a|$b)
            );
    }
    
}