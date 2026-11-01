OpenVK-KB-Heading: store.getFavoriteStickers

# store.getFavoriteStickers

Returns a list of the current user's favorite stickers.

> **Note:** In the current version of OpenVK, this method is a compatibility stub and returns an empty list.

### Authorization
Requires user authorization (`access_token`).

### Parameters
This method accepts no required parameters.

### Result

Returns an object containing:
* `count` (integer) — number of favorite stickers (`0`);
* `items` (array) — array of sticker objects (`[]`).

### Example Request
```http
POST /method/store.getFavoriteStickers HTTP/1.1
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
