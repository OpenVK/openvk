OpenVK-KB-Heading: friends.getSuggestions

# friends.getSuggestions

Returns a list of friend suggestions / recommendations for the current user.

> **Note:** In the current version of OpenVK, this method serves as a compatibility stub and returns an empty list.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `filter` | string | Recommendation filter type (e.g. `mutual`). Default: `mutual`. |
| `fields` | string | Comma-separated list of additional profile fields (e.g. `sex,bdate,photo_50`). |
| `offset` | integer | Offset needed to return a specific subset of suggestions. Default: `0`. |
| `count` | integer | Number of suggestions to return. Default: `100`. |

### Result

In API version 5.0 and higher, returns an object containing:
* `count` (integer) — `0`;
* `items` (array) — Empty array `[]`.

In API versions below 5.0, returns an empty array `[]`.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |

### Request Example
```http
POST /method/friends.getSuggestions HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

filter=mutual&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 0,
        "items": []
    }
}
```
