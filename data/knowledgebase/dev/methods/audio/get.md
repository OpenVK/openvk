OpenVK-KB-Heading: audio.get

# audio.get

Returns a list of audio files of a user or community with support for filtering, album lookup, ID list fetching, and deterministic Knuth shuffling using `shuffle_seed`.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Identifier of the audio owner (positive number for user, negative for community). Default: `0` (current user). |
| `album_id` | integer | Playlist/album identifier to fetch audios from. |
| `audio_ids` | string | Comma-separated list of audio identifiers (e.g. `1_1,1_2` or `1,2`). |
| `need_user` | integer | `1` to return an owner `user` object with each audio, `0` otherwise. Default: `1`. |
| `offset` | integer | Offset needed to return a specific subset of audios. Default: `0`. |
| `count` | integer | Number of audios to return. Default: `100`. |
| `uploaded_only` | integer | `1` to return only audios uploaded directly by the user (only valid for current user, `owner_id > 0`). Default: `0`. |
| `shuffle` | integer | `1` to enable Knuth shuffling of returned tracks, `0` for normal order. Default: `0`. |
| `need_seed` | integer | `1` to generate a random shuffle seed when `shuffle=1`. Default: `0`. |
| `shuffle_seed` | string | Base64 seed string for deterministic replay of a previously shuffled list. |
| `hash` | string | Stream authorization hash (optional). |

### Result

For API versions 5.0 and higher, returns an object with the following fields:
* `count` (integer) — total number of tracks in the result set;
* `items` (array) — array of audio objects;
* `shuffle_seed` (string, optional) — Base64 shuffle seed if `shuffle=1` was passed.

For API versions prior to 5.0, returns a plain array of audio objects.

Each audio object contains:
| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Virtual ID of the audio file in the owner collection. |
| `owner_id` | integer | Identifier of the audio owner. |
| `artist` | string | Track performer / artist. |
| `title` | string | Track title. |
| `duration` | integer | Duration in seconds. |
| `url` | string | Direct link to the MP3 file. |
| `manifest` | string | Link to MPEG-DASH manifest (`.mpd`). |
| `keys` | object | ClearKey DRM keys (if configured). |
| `genre_id` | integer | VK genre identifier (1..22, 1001; defaults to `18` — Other). |
| `genre_str` | string | OpenVK genre string (e.g., `Rock`, `Electronic`, `Pop`). |
| `global_id` | integer | Global integer ID in the OpenVK database. |
| `unique_id` | string | Base64 encoded global ID. |
| `lyrics_id` | integer | Lyrics identifier if text is available. |
| `album` | object | Album/playlist object if track is linked. |
| `album_id` | string | Formatted album ID (`owner_id_playlist_id`). |
| `added` | boolean | Whether this track is in the authorized user's library. |
| `editable` | boolean | Whether the authorized user can edit this track. |
| `searchable` | boolean | Whether the track is indexable in global search. |
| `explicit` | boolean | Whether the track is flagged as NSFW/Explicit. |
| `withdrawn` | boolean | Whether the track has been taken down. |
| `ready` | boolean | Whether the media container is fully processed. |
| `listens` | integer | Number of track listens (returned if `editable = true`). |
| `user` | object | Owner user object (`id`, `photo`, `name`, `name_gen`) when `need_user=1`. |

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `8` | Invalid parameter syntax or `uploaded_only` used with invalid `owner_id`. |
| `15` | `Access denied: this user chose to hide his audios` |
| `50` | `Invalid user` |
| `404` | `album_id invalid` |
| `600` | `Can't open this album for reading` |

### Request Example
```http
POST /method/audio.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&count=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
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
                "listens": 42
            }
        ]
    }
}
```
