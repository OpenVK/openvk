OpenVK-KB-Heading: audio.getFeed

# audio.getFeed

Returns a chronological feed of newly uploaded audio tracks across the platform with optional filtering by musical genres.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `genre_id` | integer | VK genre identifier (1..22, 1001). |
| `genre_str` | string | OpenVK genre string (e.g. `Rock`, `Pop`, `Electronic`, `Metal`, etc.). |
| `offset` | integer | Offset needed to return a specific subset of tracks. Default: `0`. |
| `count` | integer | Number of tracks to return. Default: `100`. |
| `hash` | string | Stream authorization hash (optional). |

### Result

For API versions 5.0 and higher, returns an object with:
* `count` (integer) — number of returned tracks;
* `items` (array) — array of audio objects.

For API versions prior to 5.0, returns an array formatted as `[count, audio1, audio2, ...]`.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `8` | `Invalid genre_str` or `Invalid genre ID {genre_id}` |

### Request Example
```http
POST /method/audio.getFeed HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

genre_str=Electronic&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 10,
        "items": [
            {
                "id": 5,
                "owner_id": 2,
                "artist": "Kraftwerk",
                "title": "The Robots",
                "duration": 372,
                "url": "https://openvk.instance/storage/03/hash3.mp3",
                "manifest": "https://openvk.instance/storage/03/hash3.mpd",
                "keys": {},
                "genre_id": 5,
                "genre_str": "Electronic",
                "global_id": 302,
                "unique_id": "MzAy",
                "added": false,
                "editable": false,
                "searchable": true,
                "explicit": false,
                "withdrawn": false,
                "ready": true
            }
        ]
    }
}
```
