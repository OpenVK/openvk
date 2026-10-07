OpenVK-KB-Heading: audio.removeFromAlbum

# audio.removeFromAlbum

Removes specified audio tracks from a playlist (album).

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `album_id` | integer | Identifier of the playlist. **Required.** |
| `audio_ids` | string | Comma-separated list of audio IDs (from 1 to 1000). **Required.** |

### Result

Returns `1` on successful removal.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `8` | `audio_ids must contain at least 1 audio and at most 1000` |
| `404` | `Album not found` |
| `600` | `Insufficient rights to this album` |

### Request Example
```http
POST /method/audio.removeFromAlbum HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

album_id=42&audio_ids=1_1,1_2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
