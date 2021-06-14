<?php
namespace scrAPI;

class Response {
    
    public function __construct(
        public string $status,
        public mixed $data,
        public ?int $code = null
    ) {
        if (isset($code)) {
            http_response_code($code);
        }
    }
    
    public function __toString() {
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