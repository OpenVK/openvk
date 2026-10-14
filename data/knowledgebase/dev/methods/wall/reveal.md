OpenVK-KB-Heading: wall.reveal

# wall.reveal

Restores a previously archived post back to the user or community wall.

### Authorization
Requires user authorization token with owner or administrator rights.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the wall owner (positive for user, negative for group). |
| `post_id` | integer | **Required.** Identifier of the archived post to restore. |

### Result

Returns `1` on successful unarchiving.

### Possible Errors

| Code | Description |
| --- | --- |
| `15` | `Access denied` — post not found or user lacks unarchive permissions. |

### Example Request
```http
POST /method/wall.reveal HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&post_id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
