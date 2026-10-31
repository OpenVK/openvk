OpenVK-KB-Heading: video.deleteComment

# video.deleteComment

Deletes a comment on a video.

### Authorization
Requires user authorization token. The user must be the author of the comment or the owner of the video (or community administrator).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `comment_id` | integer | Identifier of the comment to delete (alias: `cid`). |
| `cid` | integer | Alias for `comment_id`. |
| `video_id` | integer | Video identifier (optional). |
| `owner_id` | integer | Video owner identifier (optional). |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/video.deleteComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

comment_id=16&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
