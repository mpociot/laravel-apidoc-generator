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

#Group A


<!-- START_264ee15c728df32e7ca6eedce5e42dcb -->
## Example title.

This will be the long description.
It can also be multiple lines long.

> Example request:

```bash
curl -X GET \
    -G "http://localhost/api/withDescription" \
    -H "Authorization: customAuthToken" \
    -H "Custom-Header: NotSoCustom" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "http://localhost/api/withDescription"
);

let headers = {
    "Authorization": "customAuthToken",
    "Custom-Header": "NotSoCustom",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`GET api/withDescription`


<!-- END_264ee15c728df32e7ca6eedce5e42dcb -->

<!-- START_9cedd363be06f5512f9e844b100fcc9d -->
## api/withResponseTag
> Example request:

```bash
curl -X GET \
    -G "http://localhost/api/withResponseTag" \
    -H "Authorization: customAuthToken" \
    -H "Custom-Header: NotSoCustom" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "http://localhost/api/withResponseTag"
);

let headers = {
    "Authorization": "customAuthToken",
    "Custom-Header": "NotSoCustom",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (200):

```json
{
    "id": 4,
    "name": "banana",
    "color": "red",
    "weight": "1 kg",
    "delicious": true,
    "responseTag": true
}
```

### HTTP Request
`GET api/withResponseTag`


<!-- END_9cedd363be06f5512f9e844b100fcc9d -->

<!-- START_a25cb3b490fa579d7d77b386bbb7ec03 -->
## Endpoint with body parameters.

> Example request:

```bash
curl -X GET \
    -G "http://localhost/api/withBodyParameters" \
    -H "Authorization: customAuthToken" \
    -H "Custom-Header: NotSoCustom" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"user_id":9,"room_id":"consequatur","forever":false,"another_one":11613.31890586,"yet_another_param":{"name":"consequatur"},"even_more_param":[11613.31890586],"book":{"name":"consequatur","author_id":17,"pages_count":17},"ids":[17],"users":[{"first_name":"John","last_name":"Doe"}]}'

```

```javascript
const url = new URL(
    "http://localhost/api/withBodyParameters"
);

let headers = {
    "Authorization": "customAuthToken",
    "Custom-Header": "NotSoCustom",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "user_id": 9,
    "room_id": "consequatur",
    "forever": false,
    "another_one": 11613.31890586,
    "yet_another_param": {
        "name": "consequatur"
    },
    "even_more_param": [
        11613.31890586
    ],
    "book": {
        "name": "consequatur",
        "author_id": 17,
        "pages_count": 17
    },
    "ids": [
        17
    ],
    "users": [
        {
            "first_name": "John",
            "last_name": "Doe"
        }
    ]
}

fetch(url, {
    method: "GET",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`GET api/withBodyParameters`

#### Body Parameters
Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    `user_id` | integer |  required  | The id of the user.
        `room_id` | string |  optional  | The id of the room.
        `forever` | boolean |  optional  | Whether to ban the user forever.
        `another_one` | number |  optional  | Just need something here.
        `yet_another_param` | object |  required  | Some object params.
        `yet_another_param.name` | string |  required  | Subkey in the object param.
        `even_more_param` | array |  optional  | Some array params.
        `even_more_param.*` | float |  optional  | Subkey in the array param.
        `book.name` | string |  optional  | 
        `book.author_id` | integer |  optional  | 
        `book[pages_count]` | integer |  optional  | 
        `ids.*` | integer |  optional  | 
        `users.*.first_name` | string |  optional  | The first name of the user.
        `users.*.last_name` | string |  optional  | The last name of the user.
    
<!-- END_a25cb3b490fa579d7d77b386bbb7ec03 -->

<!-- START_5c545aa7f913d84b23ac4cfefc1de659 -->
## api/withQueryParameters
> Example request:

```bash
curl -X GET \
    -G "http://localhost/api/withQueryParameters?location_id=consequatur&user_id=me&page=4&filters=consequatur&url_encoded=%2B+%5B%5D%26%3D" \
    -H "Authorization: customAuthToken" \
    -H "Custom-Header: NotSoCustom" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "http://localhost/api/withQueryParameters"
);

let params = {
    "location_id": "consequatur",
    "user_id": "me",
    "page": "4",
    "filters": "consequatur",
    "url_encoded": "+ []&=",
};
Object.keys(params)
    .forEach(key => url.searchParams.append(key, params[key]));

let headers = {
    "Authorization": "customAuthToken",
    "Custom-Header": "NotSoCustom",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`GET api/withQueryParameters`

#### Query Parameters

Parameter | Status | Description
--------- | ------- | ------- | -----------
    `location_id` |  required  | The id of the location.
    `user_id` |  required  | The id of the user.
    `page` |  required  | The page number.
    `filters` |  optional  | The filters.
    `url_encoded` |  optional  | Used for testing that URL parameters will be URL-encoded where needed.

<!-- END_5c545aa7f913d84b23ac4cfefc1de659 -->

<!-- START_5c08cc4d72b6e5830f6814c64086e197 -->
## api/withAuthTag
<br><small style="padding: 1px 9px 2px;font-weight: bold;white-space: nowrap;color: #ffffff;-webkit-border-radius: 9px;-moz-border-radius: 9px;border-radius: 9px;background-color: #3a87ad;">Requires authentication</small>
> Example request:

```bash
curl -X GET \
    -G "http://localhost/api/withAuthTag" \
    -H "Authorization: customAuthToken" \
    -H "Custom-Header: NotSoCustom" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "http://localhost/api/withAuthTag"
);

let headers = {
    "Authorization": "customAuthToken",
    "Custom-Header": "NotSoCustom",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`GET api/withAuthTag`


<!-- END_5c08cc4d72b6e5830f6814c64086e197 -->

<!-- START_16ec1c69d4877579438d48a8ad8dc778 -->
## api/withEloquentApiResource
> Example request:

```bash
curl -X GET \
    -G "http://localhost/api/withEloquentApiResource" \
    -H "Authorization: customAuthToken" \
    -H "Custom-Header: NotSoCustom" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "http://localhost/api/withEloquentApiResource"
);

let headers = {
    "Authorization": "customAuthToken",
    "Custom-Header": "NotSoCustom",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (200):

```json
{
    "data": {
        "id": 4,
        "name": "Tested Again",
        "email": "a@b.com"
    }
}
```

### HTTP Request
`GET api/withEloquentApiResource`


<!-- END_16ec1c69d4877579438d48a8ad8dc778 -->

<!-- START_55f321056bfc0de7269ac70e24eb84be -->
## api/withMultipleResponseTagsAndStatusCode
> Example request:

```bash
curl -X POST \
    "http://localhost/api/withMultipleResponseTagsAndStatusCode" \
    -H "Authorization: customAuthToken" \
    -H "Custom-Header: NotSoCustom" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "http://localhost/api/withMultipleResponseTagsAndStatusCode"
);

let headers = {
    "Authorization": "customAuthToken",
    "Custom-Header": "NotSoCustom",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (200):

```json
{
    "id": 4,
    "name": "banana",
    "color": "red",
    "weight": "1 kg",
    "delicious": true,
    "multipleResponseTagsAndStatusCodes": true
}
```
> Example response (401):

```json
{
    "message": "Unauthorized"
}
```

### HTTP Request
`POST api/withMultipleResponseTagsAndStatusCode`


<!-- END_55f321056bfc0de7269ac70e24eb84be -->

#Other😎


<!-- START_c41db6ac2b427d8c29802195746cd91e -->
## api/withEloquentApiResourceCollectionClass
> Example request:

```bash
curl -X GET \
    -G "http://localhost/api/withEloquentApiResourceCollectionClass" \
    -H "Authorization: customAuthToken" \
    -H "Custom-Header: NotSoCustom" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "http://localhost/api/withEloquentApiResourceCollectionClass"
);

let headers = {
    "Authorization": "customAuthToken",
    "Custom-Header": "NotSoCustom",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (200):

```json
{
    "data": [
        {
            "id": 4,
            "name": "Tested Again",
            "email": "a@b.com"
        },
        {
            "id": 4,
            "name": "Tested Again",
            "email": "a@b.com"
        }
    ],
    "links": {
        "self": "link-value"
    }
}
```

### HTTP Request
`GET api/withEloquentApiResourceCollectionClass`


<!-- END_c41db6ac2b427d8c29802195746cd91e -->

<!-- START_33e62c07bc6d7286628b18c0e046ebea -->
## api/echoesUrlParameters/{param}-{param2}/{param3?}
> Example request:

```bash
curl -X GET \
    -G "http://localhost/api/echoesUrlParameters/4-consequatur/?something=consequatur" \
    -H "Authorization: customAuthToken" \
    -H "Custom-Header: NotSoCustom" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "http://localhost/api/echoesUrlParameters/4-consequatur/"
);

let params = {
    "something": "consequatur",
};
Object.keys(params)
    .forEach(key => url.searchParams.append(key, params[key]));

let headers = {
    "Authorization": "customAuthToken",
    "Custom-Header": "NotSoCustom",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (200):

```json
{
    "param": "4",
    "param2": "consequatur",
    "param3": null,
    "param4": null
}
```

### HTTP Request
`GET api/echoesUrlParameters/{param}-{param2}/{param3?}`

#### URL Parameters

Parameter | Status | Description
--------- | ------- | ------- | -------
    `param` |  required  | 
    `param2` |  optional  | 
    `param4` |  optional  | 
#### Query Parameters

Parameter | Status | Description
--------- | ------- | ------- | -----------
    `something` |  optional  | 

<!-- END_33e62c07bc6d7286628b18c0e046ebea -->


