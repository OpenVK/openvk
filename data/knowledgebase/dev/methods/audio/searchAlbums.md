OpenVK-KB-Heading: audio.searchAlbums

# audio.searchAlbums

Searches public playlists (albums) with filtering and custom sort ordering.

### Authorization
Requires user authorization (`access_token`) with the `audio` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `query` | string | Search query string. Default: `""` (returns all playlists). |
| `offset` | integer | Offset needed to return a specific subset of results. Default: `0`. |
| `limit` | integer | Number of results to return. Default: `25`. |
| `order` | integer | Sort order: `0` (by creation date/ID), `1` (by total length), `2` (by listens count). Default: `0`. |
| `from_me` | integer | `1` to search only playlists created by the current user, `0` across all. Default: `0`. |
| `drop_private` | integer | `1` to exclude private playlists, `0` to insert `null` for inaccessible playlists. Default: `0`. |

### Result

For API versions 5.0 and higher, returns an object with:
* `count` (integer) — number of matching playlists;
* `items` (array) — array of playlist objects.

For API versions prior to 5.0, returns an array formatted as `[count, playlist1, playlist2, ...]`.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |

### Request Example
```http
POST /method/audio.searchAlbums HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

query=Rock&order=2&limit=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 5,
                "owner_id": 1,
                "title": "Classic Rock Essentials",
                "description": "Best of classic rock",
                "size": 24,
                "length": 5840,
                "created": 1609459200,
                "modified": 1612137600,
                "accessible": true,
                "editable": false,
                "bookmarked": true,
                "listens": 450,
                "cover_url": "/storage/covers/rock.jpg",
                "searchable": true
            }
        ]
    }
}
```
