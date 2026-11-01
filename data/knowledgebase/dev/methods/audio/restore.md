OpenVK-KB-Heading: audio.restore

# audio.restore

Restores a previously deleted audio track into the user or community collection and returns its audio object.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `audio_id` | integer | Virtual ID of the audio file. **Required.** |
| `owner_id` | integer | Identifier of the audio owner. **Required.** |
| `group_id` | integer | Community identifier if restoring into a community page. |
| `hash` | string | Stream authorization hash (optional). |

### Result

Returns the restored audio object with all standard fields (`id`, `owner_id`, `artist`, `title`, `duration`, `url`, `manifest`, `keys`, `genre_id`, `genre_str`, `global_id`, `unique_id`, `added`, `editable`, `ready`).

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `201` | `Access denied to audio` |
| `203` | `Insufficient rights to this group` |
| `300` | `Album is full` |
| `404` | `Not found` / `Invalid group_id` |

### Request Example
```http
POST /method/audio.restore HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

audio_id=45&owner_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "id": 45,
        "owner_id": 1,
        "artist": "Rick Astley",
        "title": "Never Gonna Give You Up",
        "duration": 213,
        "url": "https://openvk.instance/storage/01/hash.mp3",
        "manifest": "https://openvk.instance/storage/01/hash.mpd",
        "keys": {},
        "genre_id": 2,
        "genre_str": "Pop",
        "global_id": 105,
        "unique_id": "MTA1",
        "added": true,
        "editable": true,
        "searchable": true,
        "explicit": false,
        "withdrawn": false,
        "ready": true
    }
}
```
