OpenVK-KB-Heading: newsfeed.getLists

# newsfeed.getLists

Returns custom newsfeed lists of the current user.

> [!NOTE]
> In the current version of OpenVK, this method is a stub for client compatibility and returns an empty list.

### Authorization
This method does not strictly require authorization.

### Parameters
This method takes no parameters.

### Result

Returns an object with:
* `count` (integer) — list count (always `0`);
* `items` (array) — empty array `[]`.

### Request Example
```http
POST /method/newsfeed.getLists HTTP/1.1
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
