OpenVK-KB-Heading: wall.archive

# wall.archive

Moves a wall post to the archive (hiding it from wall visitors while preserving it in the owner's personal archive).

### Authorization
Requires user authorization token with owner or administrator rights.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the wall owner (positive for user, negative for group). |
| `post_id` | integer | **Required.** Identifier of the post to archive. |

### Result

Returns `1` on successful archiving.

### Possible Errors

| Code | Description |
| --- | --- |
| `15` | `Access denied` — post not found or user lacks archive permissions. |

### Example Request
```http
POST /method/wall.archive HTTP/1.1
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
