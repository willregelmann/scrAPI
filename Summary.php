<?php
namespace scrAPI;

#[Attribute]
class Summary {
    public function __construct(
        public string $description
    ) {}
}
