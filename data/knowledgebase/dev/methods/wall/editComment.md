OpenVK-KB-Heading: wall.editComment

# wall.editComment

Edits the text and attachments of a comment on a wall post.

### Authorization
Requires user authorization token. Only the author of the comment can edit it.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `comment_id` | integer | **Required.** Identifier of the comment to edit. |
| `owner_id` | integer | Identifier of the wall owner (optional). |
| `message` | string | New comment text. |
| `attachments` | string | New comma-separated list of attachments. |

### Result

Returns `1` on success.

### Possible Errors

| Code | Description |
| --- | --- |
| `15` | `Access to editing comment denied` — current user cannot edit this comment (or comment contains a sticker). |
| `102` | `Invalid comment` — comment not found or deleted. |

### Example Request
```http
POST /method/wall.editComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

comment_id=26&message=Corrected+comment+text&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
