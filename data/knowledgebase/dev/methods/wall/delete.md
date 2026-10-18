OpenVK-KB-Heading: wall.delete

# wall.delete

Deletes a post from a user or community wall.

### Authorization
Requires user authorization token with `wall` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the wall owner (positive for user, negative for group). |
| `post_id` | integer | **Required.** Identifier of the post to delete. |

### Result

Returns `1` on successful deletion.

### Possible Errors

| Code | Description |
| --- | --- |
| `15` | `Access denied` / `Not found` — post not found or user lacks permission to delete it. |

### Example Request
```http
POST /method/wall.delete HTTP/1.1
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
