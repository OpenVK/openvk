OpenVK-KB-Heading: video.createComment

# video.createComment

Creates a new comment on a video.

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `video_id` | integer | **Required.** Video identifier. |
| `owner_id` | integer | Identifier of the video owner (positive for user, negative for group). Default: current user ID. |
| `message` | string | Comment text (alias: `text`). Required unless `sticker_id` or `attachments` is passed. |
| `text` | string | Alias for `message`. |
| `reply_to_cid` | integer | ID of the comment being replied to (alias: `reply_to_comment`). |
| `reply_to_comment` | integer | Alias for `reply_to_cid`. |
| `sticker_id` | integer | Identifier of the sticker to attach. |
| `attachments` | string | Comma-separated list of attachments (e.g. `photo1_23`). |

### Result

Returns an object containing the identifier of the created comment:

| Field | Type | Description |
| --- | --- | --- |
| `comment_id` | integer | Identifier of the created comment (in API v5.x). |

### Possible Errors

| Code | Description |
| --- | --- |
| `100` | `One of the parameters specified was missing or invalid: video not found` — video was not found or has been deleted. |
| `100` | `Required parameter 'message' is missing` — neither comment text, sticker, nor attachment was provided. |
| `100` | `Sticker not found` / `Sticker is not available for you` — sticker is missing or not owned by the user. |

### Example Request
```http
POST /method/video.createComment HTTP/1.1
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
