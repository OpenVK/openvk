OpenVK-KB-Heading: audio.moveToAlbum

# audio.moveToAlbum

Adds specified audio tracks to a playlist (album) or binds them directly to the album release.

> **Note:** The method **audio.copyToAlbum** is a direct alias of **audio.moveToAlbum**.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `album_id` | integer | Identifier of the target playlist. **Required.** |
| `audio_ids` | string | Comma-separated list of audio IDs (from 1 to 1000). **Required.** |
| `do_link` | boolean | `true` to bind audios as official releases belonging to the album (requires edit rights on each audio), `false` to merely append audios to the playlist collection. Default: `false`. |

### Result

Returns `1` on success, or `0` if no audios could be added.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `8` | `audio_ids must contain at least 1 audio and at most 1000` |
| `404` | `Album not found` |
| `600` | `Insufficient rights to this album` |

### Request Example
```http
POST /method/audio.moveToAlbum HTTP/1.1
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
