OpenVK-KB-Heading: wall.createComment

# wall.createComment

Creates a new comment on a wall post.

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the wall owner (positive for user, negative for group). |
| `post_id` | integer | **Required.** Identifier of the wall post. |
| `message` | string | Comment text. Required unless a sticker or attachment is provided. |
| `attachments` | string | Comma-separated list of attachments (`photo`, `video`, `audio`, `doc`, `note`). |
| `from_group` | integer | `1` — post comment on behalf of the community (if user has admin rights). |
| `reply_to_comment` | integer | Identifier of the comment this is replying to. |
| `sticker_id` | integer | Identifier of the sticker to send. |

### Result

Returns an object containing the identifier of the created comment:

| Field | Type | Description |
| --- | --- | --- |
| `comment_id` | integer | Identifier of the created comment (in API v5.x). |

### Possible Errors

| Code | Description |
| --- | --- |
| `15` | `Access denied` — post comments are disabled or restricted. |
| `100` | `Invalid post` — post not found or deleted. |
| `100` | `Required parameter 'message' missing.` — neither message, sticker, nor attachment was provided. |

### Example Request
```http
POST /method/wall.createComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&post_id=10&message=Thanks+for+sharing!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "comment_id": 26,
        "parents_stack": []
    }
}
```
