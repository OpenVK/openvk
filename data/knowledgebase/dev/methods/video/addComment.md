OpenVK-KB-Heading: video.addComment

# video.addComment

Adds a new comment on a video.

> **Note:** This method is an exact alias for [video.createComment](/dev/methods/video/createComment).

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `video_id` | integer | **Required.** Video identifier. |
| `owner_id` | integer | Identifier of the video owner (positive for user, negative for group). Default: current user ID. |
| `message` | string | Comment text (alias: `text`). Required unless `sticker_id` or `attachments` is provided. |
| `text` | string | Alias for `message`. |
| `reply_to_cid` | integer | ID of the comment being replied to (alias: `reply_to_comment`). |
| `reply_to_comment` | integer | Alias for `reply_to_cid`. |
| `sticker_id` | integer | Identifier of the sticker to attach. |
| `attachments` | string | Comma-separated list of attachments. |

### Result

Returns an object containing the identifier of the created comment:

| Field | Type | Description |
| --- | --- | --- |
| `comment_id` | integer | Identifier of the created comment. |

### Example Request
```http
POST /method/video.addComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&video_id=1&message=Great+video!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "comment_id": 16
    }
}
```
