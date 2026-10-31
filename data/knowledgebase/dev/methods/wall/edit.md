OpenVK-KB-Heading: wall.edit

# wall.edit

Edits an existing post on a user or community wall.

### Authorization
Requires user authorization token with edit rights for the post.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the wall owner (positive for user, negative for group). |
| `post_id` | integer | **Required.** Identifier of the post to edit. |
| `message` | string | New post text content. |
| `attachments` | string | New comma-separated list of attachments (or `"remove"` to clear all attachments). |
| `copyright` | string | New copyright source URL (or `"remove"` to delete source link). |
| `explicit` | integer | `1` — flag as NSFW, `0` — remove NSFW flag. |
| `from_group` | integer | `1` — publish on behalf of community. |
| `signed` | integer | `1` — include author signature. |

### Result

Returns an object with the identifier of the edited post:

| Field | Type | Description |
| --- | --- | --- |
| `post_id` | integer | Identifier of the edited post. |

### Possible Errors

| Code | Description |
| --- | --- |
| `7` | `Access to editing denied` — current user is not permitted to edit this post. |
| `102` | `Invalid post` — post not found or deleted. |
| `-66` | `Post will be empty, don't saving.` — cannot save a completely empty post without text and attachments. |

### Example Request
```http
POST /method/wall.edit HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&post_id=42&message=Updated+post+text&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "post_id": 42
    }
}
```
