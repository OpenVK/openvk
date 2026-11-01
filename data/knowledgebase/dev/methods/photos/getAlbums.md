OpenVK-KB-Heading: photos.getAlbums

# photos.getAlbums

Returns a list of photo albums of a user or community.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | ID of the album owner (positive for user, negative for community). Default: current user ID. |
| `album_ids` | string | Comma-separated list of album IDs. If specified, only these albums are returned. |
| `offset` | integer | Offset needed to return a specific subset of albums. Default: `0`. |
| `count` | integer | Number of albums to return. Default: `100`. |
| `need_system` | boolean | `1` — return system albums (avatars, wall photos), `0` — omit system albums. Default: `1`. |
| `need_covers` | boolean | `1` — return `thumb_src` and cover sizes, `0` — do not return. Default: `1`. |
| `photo_sizes` | boolean | `1` — return cover sizes in special `sizes` format, `0` — do not return. Default: `0`. |

### Result

In API version 5.0 and above, returns an object containing:
* `count` (integer) — total number of albums;
* `items` (array) — array of photo album objects.

In API versions below 5.0, returns an array of photo album objects directly (`[album1, album2, ...]`).

Each album object contains:
| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Photo album ID. |
| `aid` | integer | Album ID (for backwards compatibility). |
| `thumb_id` | integer | Album cover photo ID (`0` if no cover). |
| `owner_id` | integer | Album owner ID. |
| `title` | string | Album title. |
| `description` | string | Album description. |
| `created` | integer | Album creation time (Unix timestamp). |
| `updated` | integer | Album last update time (Unix timestamp). |
| `size` | integer | Number of photos in the album. |
| `privacy` | integer | Privacy level. |
| `privacy_comment` | integer | Comments privacy level. |
| `upload_by_admins_only` | integer | `1` if only admins can upload photos. |
| `comments_disabled` | integer | `0` if comments are enabled, `1` if disabled. |
| `can_upload` | integer | `1` if current user can upload photos to the album. |
| `thumb_src` | string | Album cover URL. |
| `sizes` | array | Array of size objects for the cover (if `photo_sizes=1` and `need_covers=1`). |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Albums access restricted by privacy settings. |

### Example Request
```http
POST /method/photos.getAlbums HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&need_covers=1&photo_sizes=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "aid": 1,
                "thumb_id": 10,
                "owner_id": 1,
                "title": "My Album",
                "description": "Album description",
                "created": 1609459200,
                "updated": 1609459200,
                "size": 5,
                "privacy": 0,
                "privacy_comment": 1,
                "upload_by_admins_only": 1,
                "comments_disabled": 0,
                "can_upload": 1,
                "thumb_src": "https://openvk.instance/photos/1_10.jpeg"
            }
        ]
    }
}
```
