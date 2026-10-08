OpenVK-KB-Heading: video.edit

# video.edit

Edits video information (title and description) on a user or community page.

### Authorization
Requires user access token with `video` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the video owner (positive for user, negative for group). |
| `video_id` | integer | **Required.** Video identifier. |
| `name` | string | New title for the video. |
| `desc` | string | New description for the video. |
| `no_comments` | integer | Disables comments on the video (reserved). |
| `repeat` | integer | Enables video repeat playback (reserved). |

### Result

Returns an object confirming successful modification:

| Field | Type | Description |
| --- | --- | --- |
| `success` | integer | Always `1` on success. |

### Possible Errors

| Code | Description |
| --- | --- |
| `14` | `Access denied` — video not found, deleted, or current user lacks permission to edit it. |

### Example Request
```http
POST /method/video.edit HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&video_id=1&name=Updated+Title&desc=New+Description&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "success": 1
    }
}
```
