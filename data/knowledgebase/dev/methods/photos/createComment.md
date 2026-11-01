OpenVK-KB-Heading: photos.createComment

# photos.createComment

Adds a new comment to a photo.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Photo owner ID (positive for user, negative for community). |
| `photo_id` | integer | **Required.** Photo ID. |
| `message` | string | Comment text. Required if neither `sticker_id` nor `attachments` is provided. |
| `from_group` | boolean | `1` — post comment on behalf of the community, `0` — from personal profile. Default: `0`. |
| `reply_to_comment` | integer | ID of the comment to reply to. |
| `sticker_id` | integer | ID of the sticker to send as comment. |
| `attachments` | string | Attachments string for the comment. |

### Result

Returns the ID (integer) of the created comment.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Photo not found, deleted, or commenting is restricted by privacy settings. |
| `100` | `Required parameter 'message' missing.` — Missing comment message, attachments, or sticker. |
| `100` | `Sticker not found` / `Sticker is not available for you` — Sticker not found or not available. |

### Example Request
```http
POST /method/photos.createComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&photo_id=1&message=Great%20shot!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 15
}
```
