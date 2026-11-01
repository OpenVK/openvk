OpenVK-KB-Heading: friends.getLists

# friends.getLists

Returns friend lists (categories/tags) of the current user.

> **Note:** In the current version of OpenVK, this method serves as a compatibility stub and returns an empty list.

### Authorization
This method requires user authorization (`access_token`).

### Parameters
This method does not require any parameters.

### Result

Returns an object containing:
* `count` (integer) — `0`;
* `items` (array) — Empty array `[]`.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |

### Request Example
```http
POST /method/friends.getLists HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
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
