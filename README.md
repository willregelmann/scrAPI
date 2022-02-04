# ScrAPI

ScrAPI is a framework for implementing self-documenting, highly extensible REST APIs in PHP.

## Requirements

- PHP >= 8.0.0

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
