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
<!-- START_264ee15c728df32e7ca6eedce5e42dcb -->
## Example title.
 
This will be the long description.
It can also be multiple lines long.

> Example request:

```bash
curl -X GET -G "http://localhost/api/withDescription" \
    -H "Accept: application/json" \
    -H "Authorization: customAuthToken" \
        -H "Custom-Header: NotSoCustom" 
```

```javascript
const url = new URL("http://localhost/api/users");

let headers = {
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response:

```json
null
```

### HTTP Request
`GET api/withDescription`


<!-- END_264ee15c728df32e7ca6eedce5e42dcb -->

<!-- START_9cedd363be06f5512f9e844b100fcc9d -->
## api/withResponseTag
 
> Example request:

```bash
curl -X GET -G "http://localhost/api/withResponseTag" \
    -H "Accept: application/json" \
    -H "Authorization: customAuthToken" \
        -H "Custom-Header: NotSoCustom" 
```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "http://localhost/api/withResponseTag",
    "method": "GET",
    "headers": {
        "accept": "application/json",
        "Authorization": "customAuthToken",
        "Custom-Header": "NotSoCustom",
    }
}

$.ajax(settings).done(function (response) {
    console.log(response);
});
```

> Example response:

```json
{
    "id": 4,
    "name": "banana",
    "color": "red",
    "weight": "1 kg",
    "delicious": true
}
```

### HTTP Request
`GET api/withResponseTag`


<!-- END_9cedd363be06f5512f9e844b100fcc9d -->

<!-- START_a25cb3b490fa579d7d77b386bbb7ec03 -->
## api/withBodyParameters
 
> Example request:

```bash
curl -X GET -G "http://localhost/api/withBodyParameters" \
    -H "Accept: application/json" \
    -H "Authorization: customAuthToken" \
        -H "Custom-Header: NotSoCustom"  \
    -d "user_id"=20 \
        -d "room_id"=6DZyNcBgezdjdAIs \
        -d "forever"= \
        -d "another_one"=2153.4 \
        -d "yet_another_param"={} \
        -d "even_more_param"=[] 
```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "http://localhost/api/withBodyParameters",
    "method": "GET",
    "data": {
        "user_id": 20,
        "room_id": "6DZyNcBgezdjdAIs",
        "forever": false,
        "another_one": 2153.4,
        "yet_another_param": "{}",
        "even_more_param": "[]"
    },
    "headers": {
        "accept": "application/json",
        "Authorization": "customAuthToken",
        "Custom-Header": "NotSoCustom",
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
`GET api/withBodyParameters`

#### Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    user_id | integer |  required  | The id of the user.
    room_id | string |  optional  | The id of the room.
    forever | boolean |  optional  | Whether to ban the user forever.
    another_one | number |  optional  | Just need something here.
    yet_another_param | object |  required  | 
    even_more_param | array |  optional  | 

<!-- END_a25cb3b490fa579d7d77b386bbb7ec03 -->

<!-- START_5c08cc4d72b6e5830f6814c64086e197 -->
## api/withAuthTag
 <small style="
  padding: 1px 9px 2px;
  font-weight: bold;
  white-space: nowrap;
  color: #ffffff;
  -webkit-border-radius: 9px;
  -moz-border-radius: 9px;
  border-radius: 9px;
  background-color: #3a87ad;">Requires authentication</small>

> Example request:

```bash
curl -X GET -G "http://localhost/api/withAuthTag" \
    -H "Accept: application/json" \
    -H "Authorization: customAuthToken" \
        -H "Custom-Header: NotSoCustom" 
```

```javascript
var settings = {
    "async": true,
    "crossDomain": true,
    "url": "http://localhost/api/withAuthTag",
    "method": "GET",
    "headers": {
        "accept": "application/json",
        "Authorization": "customAuthToken",
        "Custom-Header": "NotSoCustom",
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
`GET api/withAuthTag`


<!-- END_5c08cc4d72b6e5830f6814c64086e197 -->


