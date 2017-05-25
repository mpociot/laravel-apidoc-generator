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
<!-- START_08307893aff90cc5097c48a1c8fc2f6d -->
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


<!-- END_08307893aff90cc5097c48a1c8fc2f6d -->

<!-- START_8ba174f2507a0967efd46fab3764b80e -->
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


<!-- END_8ba174f2507a0967efd46fab3764b80e -->

