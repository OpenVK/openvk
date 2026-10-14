OpenVK-KB-Heading: store.getRecentStickers

# store.getRecentStickers

Returns a list of recently used stickers by the current user.

> **Note:** In the current version of OpenVK, this method is a compatibility stub and returns an empty list.

### Authorization
Requires user authorization (`access_token`).

### Parameters
This method accepts no required parameters.

### Result

Returns an object containing:
* `count` (integer) — number of recent stickers (`0`);
* `items` (array) — array of sticker objects (`[]`).

### Example Request
```http
POST /method/store.getRecentStickers HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 0,
        "items": []
    }
}
```
