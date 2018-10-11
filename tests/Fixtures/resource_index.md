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
<!-- START_2b6e5a4b188cb183c7e59558cce36cb6 -->
## Display a listing of the resource.

> Example request:

```bash
curl -X GET -G "http://localhost/api/users" \
    -H "Accept: application/json"
```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "http://localhost/api/users",
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
    "index_resource": true
}
```

### HTTP Request
`GET api/users`


<!-- END_2b6e5a4b188cb183c7e59558cce36cb6 -->

<!-- START_7f66c974d24032cb19061d55d801f62b -->
## Show the form for creating a new resource.

> Example request:

```bash
curl -X GET -G "http://localhost/api/users/create" \
    -H "Accept: application/json"
```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "http://localhost/api/users/create",
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
    "create_resource": true
}
```

### HTTP Request
`GET api/users/create`


<!-- END_7f66c974d24032cb19061d55d801f62b -->

<!-- START_f0654d3f2fc63c11f5723f233cc53c83 -->
## Store a newly created resource in storage.

> Example request:

```bash
curl -X POST "http://localhost/api/users" \
    -H "Accept: application/json"
```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "http://localhost/api/users",
    "method": "POST",
    "headers": {
        "accept": "application/json"
    }
}

$.ajax(settings).done(function (response) {
    console.log(response);
});
```


### HTTP Request
`POST api/users`


<!-- END_f0654d3f2fc63c11f5723f233cc53c83 -->

<!-- START_ceec0e0b1d13d731ad96603d26bccc2f -->
## Display the specified resource.

> Example request:

```bash
curl -X GET -G "http://localhost/api/users/{user}" \
    -H "Accept: application/json"
```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "http://localhost/api/users/{user}",
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
    "show_resource": "1"
}
```

### HTTP Request
`GET api/users/{user}`


<!-- END_ceec0e0b1d13d731ad96603d26bccc2f -->

<!-- START_f4aa12af19ba08e1448d7eafc9f55e67 -->
## Show the form for editing the specified resource.

> Example request:

```bash
curl -X GET -G "http://localhost/api/users/{user}/edit" \
    -H "Accept: application/json"
```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "http://localhost/api/users/{user}/edit",
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
    "edit_resource": "1"
}
```

### HTTP Request
`GET api/users/{user}/edit`


<!-- END_f4aa12af19ba08e1448d7eafc9f55e67 -->

<!-- START_a4a2abed1e8e8cad5e6a3282812fe3f3 -->
## Update the specified resource in storage.

> Example request:

```bash
curl -X PUT "http://localhost/api/users/{user}" \
    -H "Accept: application/json"
```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "http://localhost/api/users/{user}",
    "method": "PUT",
    "headers": {
        "accept": "application/json"
    }
}

$.ajax(settings).done(function (response) {
    console.log(response);
});
```


### HTTP Request
`PUT api/users/{user}`

`PATCH api/users/{user}`


<!-- END_a4a2abed1e8e8cad5e6a3282812fe3f3 -->

<!-- START_4bb7fb4a7501d3cb1ed21acfc3b205a9 -->
## Remove the specified resource from storage.

> Example request:

```bash
curl -X DELETE "http://localhost/api/users/{user}" \
    -H "Accept: application/json"
```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "http://localhost/api/users/{user}",
    "method": "DELETE",
    "headers": {
        "accept": "application/json"
    }
}

$.ajax(settings).done(function (response) {
    console.log(response);
});
```


### HTTP Request
`DELETE api/users/{user}`


<!-- END_4bb7fb4a7501d3cb1ed21acfc3b205a9 -->


