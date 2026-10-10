OpenVK-KB-Heading: audio.getPopular

# audio.getPopular

Returns a list of the most popular audio tracks across the platform (sorted by listen count) with optional filtering by musical genres.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `genre_id` | integer | VK genre identifier (`1` — Rock, `2` — Pop, `3` — Rap & Hip-Hop, `4` — Easy Listening, `5` — Dance & House, `6` — Instrumental, `7` — Metal, `8` — Dubstep, `10` — Drum & Bass, `11` — Trance, `12` — Chanson, `13` — Ethnic, `14` — Acoustic & Vocal, `15` — Reggae, `16` — Classical, `17` — Indie Pop, `18` — Other, `19` — Speech, `21` — Alternative, `22` — Disco, `1001` — Jazz & Blues). |
| `genre_str` | string | OpenVK genre string (e.g. `Rock`, `Pop`, `Electronic`, `Metal`, `Jazz`, etc.). |
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
| `8` | `Invalid genre_str` or `Invalid genre ID {genre_id}` — Specified genre is unrecognized. |

### Request Example
```http
POST /method/audio.getPopular HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

genre_id=1&count=20&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 20,
        "items": [
            {
                "id": 1,
                "owner_id": 1,
                "artist": "Queen",
                "title": "Bohemian Rhapsody",
                "duration": 354,
                "url": "https://openvk.instance/storage/01/hash.mp3",
                "manifest": "https://openvk.instance/storage/01/hash.mpd",
                "keys": {},
                "genre_id": 1,
                "genre_str": "Rock",
                "global_id": 12,
                "unique_id": "MTI=",
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
