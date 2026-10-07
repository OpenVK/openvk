OpenVK-KB-Heading: audio.getById

# audio.getById

Returns detailed information about one or more audio files specified by their identifiers. Supports fetching up to 6,000 audios in a single API call.

Identifiers can be passed in different formats:
* `owner_id_vid` — combination of owner ID and virtual ID (e.g. `1_42`);
* `id` — global integer database ID (e.g. `105`);
* `unique_id` — Base64 string of global ID (e.g. `MTA1`).

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `audios` | string | Comma-separated list of audio IDs (e.g. `1_1,1_2,105`). Maximum: 6,000. **Required.** |
| `need_user` | integer | `1` to include owner's `user` object (`id`, `photo`, `name`, `name_gen`), `0` otherwise. Default: `0`. |
| `hash` | string | Stream authorization hash (optional). |

### Result

* In API version **5.138 and higher**, returns an object with:
  * `count` (integer) — number of returned tracks;
  * `items` (array) — array of audio objects.
* In API version **prior to 5.138**, returns a flat array of audio objects.

Each audio object includes standard fields: `id`, `owner_id`, `artist`, `title`, `duration`, `url`, `manifest`, `keys`, `genre_id`, `genre_str`, `global_id`, `unique_id`, `lyrics_id`, `album`, `added`, `editable`, `searchable`, `explicit`, `withdrawn`, `ready`.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `8` | `Invalid audio {id}` — Malformed audio identifier. |
| `201` | `Access denied to audio({id})` — Privacy restrictions prevent reading this audio. |
| `404` | `Audio not found` |
| `1980` | `Can't get more than 6000 audios at once` |

### Request Example
```http
POST /method/audio.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

audios=1_1,1_2&need_user=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 2,
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
                "editable": true,
                "searchable": true,
                "explicit": false,
                "withdrawn": false,
                "ready": true,
                "user": {
                    "id": 1,
                    "photo": "/storage/avatars/1.jpg",
                    "name": "Pavel Durov",
                    "name_gen": "Pavel Durov"
                }
            }
        ]
    }
}
```
