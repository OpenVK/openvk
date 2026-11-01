OpenVK-KB-Heading: video.delete

# video.delete

Deletes a video from a user or community page.

### Authorization
Requires user access token with `video` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the video owner (positive for user, negative for group). |
| `video_id` | integer | **Required.** Video identifier. |
| `target_id` | integer | Target owner identifier when removing from saved collections (reserved). |

### Result

Returns `1` upon successful deletion.

### Possible Errors

| Code | Description |
| --- | --- |
| `14` | `Access denied` — video not found, already deleted, or current user lacks permission to delete it. |
| `-40` | `Videos cannot be collected at this moment.` — removing collected videos is currently unsupported. |

### Example Request
```http
POST /method/video.delete HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&video_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
