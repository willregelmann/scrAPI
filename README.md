# ScrAPI

ScrAPI is a framework for implementing self-documenting, highly extensible REST APIs in PHP.

## Requirements

- PHP >= 8.0.0

## Adding Collections

ScrAPI works with "Collections", which are individual classes representing API endpoints. The behavior of HTTP requests to a Collection is defined by attributes on specific methods. The Method() attribute takes an HTTP verb as an argument, and optionally a relative request path. For example, `Method('GET')` specifies that the method should be called when an HTTP GET request is made to the endpoint (`{base uri}/{endpoint}/`). `Method('GET', '{id}')` indicates that the method should be called when an HTTP GET request is made to `{base uri}/{endpoint}/{some id}`, with the ID being passed to that method automatically. Method names are arbitrary: only the Method attribute is checked. 

Collections are subject to the following rules:
- The Collection class name should match the file name exactly
- The namespace should begin with scrAPI\Collections. If the Collection is in a subdirectory within the Collections folder, include that directory in the namespace (e.g. scrAPI\Collections\dir)

An example Collection is provided below.

```php
<?php
namespace scrAPI\Collections;
use scrAPI\Schemas\Sample;
use scrAPI\{
    Method,
    Summary
};

final class Samples {

    #[
        Method("GET"),
        Summary("List all samples"),
    ]
    public static function list():array {
        /*
        Code to return a list of all Samples
        */
    }
    
    #[
        Method("GET", "{id}"),
        Summary("Retrieve a sample by ID")
    ]
    public static function read(int $id):?Sample {
        /*
        Code to retrieve a single Sample by ID
        */
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
    public static function update(int $id, #[In("body")] ?Sample $new_sample):?Sample {
        /*
        Code to replace a single Sample by ID
        */
        return self::read($id);
    }

}
```

The above collection implies the following valid HTTP requests:
- `GET /api/Samples`, which returns an array containing all Samples
- `GET /api/Samples/1`, which returns the Sample with ID 1
- `PUT /api/Samples/1`, which overwrites 

As a matter of best practice, a Schema should be defined for each Collection, and methods which return a single item from a collection should explicitly return an instance of that Schema.

## Schemas

## API Documentation

## Server Configuration

Regardless of directory structure, all API requests should be passed to handler.php (with the exception of the base path, which may be passed to docs.php). Example web server configuration is below.

Apache:
```
RewriteRule ^api/?$       /api/docs.php     [END]
RewriteRule ^api/(.*).+$  /api/handler.php  [QSA,END]
```

Nginx:
```
rewrite ^/api/?$    /api/docs.php     last;
rewrite ^/api/(.*)$ /api/handler.php  last;
```
