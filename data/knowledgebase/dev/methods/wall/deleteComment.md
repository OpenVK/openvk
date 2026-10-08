OpenVK-KB-Heading: wall.deleteComment

# wall.deleteComment

Deletes a comment from a wall post.

### Authorization
Requires user authorization token. User must be the author of the comment or the owner/admin of the wall.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `comment_id` | integer | Identifier of the comment to delete (alias: `cid`). |
| `cid` | integer | Alias for `comment_id`. |
| `owner_id` | integer | Identifier of the wall owner (optional). |

### Result

Returns `1` on success.

### Possible Errors

| Code | Description |
| --- | --- |
| `7` | `Access denied` — user lacks permission to delete the comment. |
| `100` | `One of the parameters specified was missing or invalid` — comment not found. |

### Example Request
```http
POST /method/wall.deleteComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

comment_id=26&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
