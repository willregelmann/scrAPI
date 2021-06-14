<?php
namespace scrAPI;

#[Attribute]
class Method {
    public function __construct(
        public string $verb,
        public ?string $path = null
    ) {}
}
