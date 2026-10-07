OpenVK-KB-Heading: audio.search

# audio.search

Performs full-text search across public audio files with filters for artist name, lyrics presence, and sorting order.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `q` | string | Search query (artist name, track title, or lyrics text). **Required.** |
| `performer_only` | integer | `1` to search only by performer/artist name, `0` to search across all fields. Default: `0`. |
| `lyrics` | integer | `1` to return only tracks with available lyrics, `0` otherwise. Default: `0`. |
| `sort` | integer | Sort order: `2` (by listens/popularity), `1` (by length), `0` (by date added). Default: `2`. |
| `offset` | integer | Offset needed to return a specific subset of results. Default: `0`. |
| `count` | integer | Number of results to return (from `1` to `300`). Default: `30`. |
| `auto_complete` | integer | *(Not supported)* Must be `0`. Passing `1` raises error 10. |
| `search_own` | integer | *(Not supported)* Must be `0`. Passing `1` raises error 10. |
| `hash` | string | Stream authorization hash (optional). |

### Result

For API versions 5.0 and higher, returns an object with:
* `count` (integer) — number of matching audio files;
* `items` (array) — array of audio objects.

For API versions prior to 5.0, returns an array formatted as `[count, audio1, audio2, ...]`.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `8` | `count is invalid: {count}` — Parameter `count` is out of range `1..300`. |
| `10` | `auto_complete and search_own are not supported` — Unsupported parameters enabled. |

### Request Example
```http
POST /method/audio.search HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=Astley&performer_only=1&sort=2&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
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
                "lyrics_id": 105,
                "added": true,
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
