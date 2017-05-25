---
title: API Reference

language_tabs:
- bash
- javascript

includes:

search: true

toc_footers:
- <a href='http://github.com/mpociot/documentarian'>Documentation Powered by Documentarian</a>
---
<!-- START_INFO -->
# Info

Welcome to the generated API reference.
[Get Postman Collection](http://localhost/docs/collection.json)

<!-- END_INFO -->

#general
<!-- START_0bef4e738c9d6720ad43b062015d1078 -->
## Example title.

This will be the long description.
It can also be multiple lines long.

> Example request:

```bash
curl -X GET "http://localhost/api/test" \
-H "Accept: application/json"
```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "http://localhost/api/test",
    "method": "GET",
    "headers": {
        "accept": "application/json"
    }
}

$.ajax(settings).done(function (response) {
    console.log(response);
});
```

> Example response:

```json
null
```

### HTTP Request
`GET api/test`


<!-- END_0bef4e738c9d6720ad43b062015d1078 -->

<!-- START_960a1b2b0f0f4dde8ce993307397f9c4 -->
## api/fetch

> Example request:

```bash
curl -X GET "http://localhost/api/fetch" \
-H "Accept: application/json"
```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "http://localhost/api/fetch",
    "method": "GET",
    "headers": {
        "accept": "application/json"
    }
}

$.ajax(settings).done(function (response) {
    console.log(response);
});
```

> Example response:

```json
{
    "id": 1,
    "name": "Banana",
    "color": "Red",
    "weight": "300 grams",
    "delicious": true
}
```

### HTTP Request
`GET api/fetch`


<!-- END_960a1b2b0f0f4dde8ce993307397f9c4 -->

