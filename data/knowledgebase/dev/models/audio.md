OpenVK-KB-Heading: Audio Object

# Audio Object

The **Audio** object describes a music track in OpenVK / VKontakte. The exact field names depend on the API version passed in `v`.

---

## API Version 5.0 and Higher (v >= 5.0)

API versions 5.0+ return standard modern field names:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Audio track ID. |
| `owner_id` | integer | Owner ID (user or community ID). |
| `artist` | string | Artist / performer name. |
| `title` | string | Track title. |
| `duration` | integer | Track length in seconds. |
| `url` | string | Direct streaming URL (MP3). |
| `lyrics_id` | integer | *(Optional)* Lyrics ID if lyrics are available. |
| `album_id` | integer | *(Optional)* Album ID. |
| `genre_id` | integer | Genre ID code (`1` — Rock, `2` — Pop, `3` — Rap & Hip-Hop, `4` — Easy Listening, `5` — Dance & House, `6` — Instrumental, `7` — Metal, `8` — Dubstep, `9` — Drum & Bass, `10` — Trance, `11` — Chanson, `12` — Ethnic, `13` — Acoustic & Vocal, `14` — Reggae, `15` — Classical, `16` — Indie Pop, `17` — Speech, `18` — Other). |
| `genre_str` | string | Genre string name. |
| `added` | boolean | Whether the track is added to current user's library. |
| `editable` | boolean | Whether the current user can edit this audio track. |
| `searchable` | boolean | Whether the track is available in global search. |
| `explicit` | boolean | Whether the track contains explicit content. |
| `listens` | integer | *(Optional)* Total play count (returned if `editable = true`). |

### JSON Example (v >= 5.0)
```json
{
    "id": 1,
    "owner_id": 1,
    "artist": "Neverlove",
    "title": "Driving Instructor",
    "duration": 184,
    "url": "https://openvk.instance/audio/get/1.mp3",
    "genre_id": 1,
    "genre_str": "Rock",
    "added": true,
    "editable": false,
    "searchable": true,
    "explicit": true
}
```

---

## Below API Version 5.0 (v < 5.0)

In earlier API versions (3.x, 4.x), legacy field names are returned:

| Field | Type | Description |
| --- | --- | --- |
| `aid` / `id` | integer | Audio ID. |
| `oid` / `owner_id` | integer | Owner user or group ID. |
| `performer` / `artist` | string | Artist name. |
| `title` | string | Track title. |
| `duration` | integer | Length in seconds. |
| `url` | string | MP3 audio stream URL. |
| `lyricsID` / `lyrics_id` | integer | *(Optional)* Lyrics ID. |
| `genre` / `genre_id` | integer | Genre ID number. |

### JSON Example (v < 5.0)
```json
{
    "aid": 1,
    "oid": 1,
    "performer": "Neverlove",
    "title": "Driving Instructor",
    "duration": 184,
    "url": "https://openvk.instance/audio/get/1.mp3",
    "genre": 1
}
```
