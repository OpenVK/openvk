OpenVK-KB-Heading: board.createComment

# board.createComment

Adds a new comment (text, attachment, or sticker) to a topic on a community discussion board.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | Community identifier. **Required.** |
| `topic_id` | integer | Topic identifier inside the community. **Required.** |
| `message` | string | Comment text. Required if `attachments` and `sticker_id` are omitted. |
| `from_group` | boolean | `true` to post comment on behalf of the community (if user is an admin), `false` on behalf of user. Default: `true`. |
| `sticker_id` | integer | Sticker identifier. When provided, `message` text is ignored. |
| `attachments` | string | Comma-separated list of attachments (e.g. `photo1_42`). |

### Result

Returns the integer identifier of the created comment (`comment_id`).

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `15` | `Access denied` — Topic is missing, deleted, or closed. |
| `100` | `Required parameter 'message' missing.` / `Sticker not found` / `Sticker is not available for you` |

### Request Example
```http
POST /method/board.createComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&topic_id=1&message=Great+news!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 102
}
```
