OpenVK-KB-Heading: audio.search

# audio.search

Returns a list of audio files matching a search query.

### Authorization
This method requires `audio` access permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `q` | string | Search query string. Required. |
| `performer_only` | integer | `1` — search by artist name only. Default is `0`. |
| `lyrics` | integer | `1` — search only for tracks with lyrics. Default is `0`. |
| `sort` | integer | Sort order: `2` — by popularity, `1` — by duration, `0` — by date added. Default is `2`. |
| `offset` | integer | Offset needed to return a specific subset of results. Default is `0`. |
| `count` | integer | Number of audio files to return. Default is `30`, maximum is `300`. |

### Result
Returns an object with `count` (total number of results) and `items` (array of audio objects).

### Example Response

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
                "url": "https://openvk.instance/storage/audio/1_1.mp3"
            }
        ]
    }
}
```
