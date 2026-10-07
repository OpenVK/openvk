OpenVK-KB-Heading: audio.unBookmarkAlbum

# audio.unBookmarkAlbum

Removes a playlist (album) from the authorized user's bookmarks.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `id` | integer | Identifier of the playlist. **Required.** |

### Result

Returns `1` on success, or `0` if the playlist was not bookmarked.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `404` | `Not found` |
| `600` | `Access error` |

### Request Example
```http
POST /method/audio.unBookmarkAlbum HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
