OpenVK-KB-Heading: wall.unpin

# wall.unpin

Unpins a previously pinned post from the top of a user or community wall.

### Authorization
Requires user authorization token with owner or administrator rights.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the wall owner (positive for user, negative for group). |
| `post_id` | integer | **Required.** Identifier of the post to unpin. |

### Result

Returns `1` on successful unpinning, or `0` if user lacks unpin permissions.

### Possible Errors

| Code | Description |
| --- | --- |
| `100` | `One of the parameters specified was missing or invalid: post_id is undefined` — post not found or deleted. |

### Example Request
```http
POST /method/wall.unpin HTTP/1.1
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
