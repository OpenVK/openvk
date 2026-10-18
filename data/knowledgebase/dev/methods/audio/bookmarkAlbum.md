OpenVK-KB-Heading: audio.bookmarkAlbum

# audio.bookmarkAlbum

Bookmarks a playlist (album) for the currently authorized user.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `id` | integer | Identifier of the playlist to bookmark. **Required.** |

### Result

Returns `1` on success, or `0` if the playlist was already bookmarked.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `404` | `Not found` |
| `600` | `Access error` |

### Request Example
```http
POST /method/audio.bookmarkAlbum HTTP/1.1
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
