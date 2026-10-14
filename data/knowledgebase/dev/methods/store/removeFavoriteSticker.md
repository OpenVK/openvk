OpenVK-KB-Heading: store.removeFavoriteSticker

# store.removeFavoriteSticker

Removes a sticker from the user's favorite stickers list.

> **Note:** In OpenVK, this method is a compatibility stub and returns `{"success": 1}`.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `sticker_id` | integer | **Required.** ID of the sticker to remove. |

### Result

Returns an object containing:
| Field | Type | Description |
| --- | --- | --- |
| `success` | integer | Always `1`. |

### Example Request
```http
POST /method/store.removeFavoriteSticker HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

sticker_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "success": 1
    }
}
```
