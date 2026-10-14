OpenVK-KB-Heading: store.clearRecentStickers

# store.clearRecentStickers

Clears the user's recently used stickers list.

> **Note:** In OpenVK, this method is a compatibility stub and returns `{"success": 1}`.

### Authorization
Requires user authorization (`access_token`).

### Parameters
This method accepts no required parameters.

### Result

Returns an object containing:
| Field | Type | Description |
| --- | --- | --- |
| `success` | integer | Always `1`. |

### Example Request
```http
POST /method/store.clearRecentStickers HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "success": 1
    }
}
```
