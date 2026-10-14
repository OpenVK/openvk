OpenVK-KB-Heading: audio.getPlaylistById

# audio.getPlaylistById

Returns basic playlist information by its virtual ID and owner ID.

### Authorization
This method does not strictly require user authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Identifier of the playlist owner. Default: `0`. |
| `playlist_id` | integer | Virtual ID of the playlist. Default: `0`. |

### Result

Returns an object with fields:
* `id` (integer) — global playlist ID;
* `owner_id` (integer) — owner ID;
* `title` (string) — playlist title;
* `cover_url` (string) — cover image URL.

### Error Codes

| Code | Description |
| --- | --- |
| `15` | `Access error` — Playlist was not found, deleted, or is inaccessible. |

### Request Example
```http
POST /method/audio.getPlaylistById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&playlist_id=1&v=5.138
```

### Response Example
```json
{
    "response": {
        "id": 1,
        "owner_id": 1,
        "title": "Favorite Music",
        "cover_url": "/storage/covers/playlist_1.jpg"
    }
}
```
