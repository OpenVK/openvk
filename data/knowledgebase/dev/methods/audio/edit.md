OpenVK-KB-Heading: audio.edit

# audio.edit

Edits metadata of an audio track (artist, title, lyrics text, genre, and search indexability).

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Identifier of the audio owner. **Required.** |
| `audio_id` | integer | Virtual ID of the audio file. **Required.** |
| `artist` | string | New artist / performer name. |
| `title` | string | New track title. |
| `text` | string | Lyrics text. |
| `genre_id` | integer | VK genre identifier (1..22, 1001). |
| `genre_str` | string | OpenVK genre string (e.g. `Rock`, `Pop`, `Electronic`). |
| `no_search` | integer | `1` to exclude track from global search results, `0` to keep indexable. Default: `0`. |

### Result

Returns the lyrics identifier (`lyrics_id`) if the `text` parameter was provided, or `0` otherwise.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `8` | `Invalid genre ID {genre_id}` or `Invalid genre_str` |
| `201` | `Insufficient permissions to edit this audio` |
| `404` | `Not Found` |

### Request Example
```http
POST /method/audio.edit HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&audio_id=45&artist=Rick+Astley&title=Never+Gonna+Give+You+Up+(Remastered)&genre_id=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 0
}
```
