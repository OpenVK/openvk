OpenVK-KB-Heading: audio.get

# audio.get

Returns a list of audio files of a user or community.

### Authorization
This method requires `audio` access permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | ID of the user or community that owns the audio files. Default is current user ID. |
| `album_id` | integer | Playlist (album) ID. |
| `audio_ids` | string | Audio IDs separated by commas. |
| `need_user` | integer | `1` — return information about the user who uploaded the audio. Default is `1`. |
| `offset` | integer | Offset needed to return a specific subset of audio files. Default is `0`. |
| `count` | integer | Number of audio files to return. Default is `100`, maximum is `100`. |
| `shuffle` | integer | `1` — shuffle audio tracks in random order. Default is `0`. |

### Result
Returns an object with `count` (total number of tracks) and `items` (array of audio objects).

Each audio object contains:
* `id` — audio ID;
* `owner_id` — owner ID;
* `artist` — artist name;
* `title` — track title;
* `duration` — duration in seconds;
* `url` — direct link to the MP3 file;
* `lyrics_id` — lyrics ID if available.

### Example Response

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
                "url": "https://ovk.to/storage/audio/1_1.mp3"
            },
            {
                "id": 2,
                "owner_id": 1,
                "artist": "Darude",
                "title": "Sandstorm",
                "duration": 225,
                "url": "https://ovk.to/storage/audio/1_2.mp3"
            }
        ]
    }
}
```
