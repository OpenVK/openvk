OpenVK-KB-Heading: wall.addComment

# wall.addComment

Adds a comment to a wall post.

> **Note:** This method is a convenient alias for [wall.createComment](/dev/methods/wall/createComment).

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the wall owner. |
| `post_id` | integer | **Required.** Identifier of the wall post. |
| `text` | string | Comment text (alias: `message`). |
| `message` | string | Alias for `text`. |
| `reply_to_cid` | integer | Identifier of parent comment (alias: `reply_to_comment`). |
| `reply_to_comment` | integer | Alias for `reply_to_cid`. |
| `attachments` | string | Comma-separated list of attachments. |
| `from_group` | integer | `1` — post comment on behalf of the community. |
| `sticker_id` | integer | Identifier of sticker to send. |

### Result

Returns an object with the created `comment_id`.

### Example Request
```http
POST /method/wall.addComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&post_id=10&text=Great+post!&access_token=YOUR_ACCESS_TOKEN&v=5.138
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
