OpenVK-KB-Heading: audio.deleteAlbum

# audio.deleteAlbum

Deletes a playlist (album).

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `album_id` | integer | Identifier of the playlist to delete. **Required.** |

### Result

Returns `1` on successful deletion.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `404` | `Album not found` |
| `600` | `Insufficient rights to this album` |

### Request Example
```http
POST /method/audio.deleteAlbum HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

album_id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
