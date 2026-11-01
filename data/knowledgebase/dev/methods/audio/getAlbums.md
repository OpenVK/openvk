OpenVK-KB-Heading: audio.getAlbums

# audio.getAlbums

Returns a list of playlists (albums) of a user or community.

> **Note:** The method **audio.getPlaylists** is a direct alias of **audio.getAlbums**.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Identifier of the owner (positive for user, negative for community). Default: `0` (current user). |
| `offset` | integer | Offset needed to return a specific subset of playlists. Default: `0`. |
| `count` | integer | Number of playlists to return. Default: `50`. |
| `drop_private` | integer | `1` to exclude private/hidden playlists, `0` to include `null` entries for inaccessible playlists. Default: `1`. |

### Result

For API versions 5.0 and higher, returns an object with:
* `count` (integer) — number of playlists;
* `items` (array) — array of playlist (album) objects.

For API versions prior to 5.0, returns an array formatted as `[count, playlist1, playlist2, ...]`.

Each playlist object contains:
| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Playlist identifier. |
| `owner_id` | integer | Identifier of the playlist owner. |
| `title` | string | Playlist title. |
| `description` | string | Playlist description. |
| `size` | integer | Number of audio tracks in the playlist. |
| `length` | integer | Total duration of all tracks in seconds. |
| `created` | integer | Creation time (Unix timestamp). |
| `modified` | integer | Last edit time (Unix timestamp or `null`). |
| `accessible` | boolean | Whether playlist is viewable by the current user. |
| `editable` | boolean | Whether playlist can be modified by the current user. |
| `bookmarked` | boolean | Whether playlist is bookmarked by the current user. |
| `listens` | integer | Total playlist listens count. |
| `cover_url` | string | Playlist cover image URL. |
| `searchable` | boolean | Whether playlist is indexable in search. |
| `thumb` | object | Thumbnail sizes object (if custom photo cover is set). |

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `50` | `Invalid user` or `Access to playlists denied` |

### Request Example
```http
POST /method/audio.getAlbums HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
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
                "title": "Favorite Music",
                "description": "Best tracks of all time",
                "size": 15,
                "length": 3420,
                "created": 1609459200,
                "modified": 1612137600,
                "accessible": true,
                "editable": true,
                "bookmarked": false,
                "listens": 120,
                "cover_url": "/storage/covers/playlist_1.jpg",
                "searchable": true
            }
        ]
    }
}
```
