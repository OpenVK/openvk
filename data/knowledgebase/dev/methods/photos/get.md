OpenVK-KB-Heading: photos.get

# photos.get

Returns a list of photos from a specific album or by a list of photo IDs.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** ID of the album owner (positive for user, negative for community). |
| `album_id` | string | Album ID or the string `profile` (for profile avatars album). Default: `profile`. |
| `photo_ids` | string | Comma-separated list of photo IDs in the format `owner_id_photo_id` (maximum 78). If passed, `album_id` is ignored. |
| `extended` | boolean | `1` — return extra fields (`likes`, `comments`, `can_comment`, `can_repost`), `0` — do not return. Default: `0`. |
| `photo_sizes` | boolean | `1` — return array of photo sizes in `sizes`, `0` — do not return. Default: `1`. |
| `offset` | integer | Offset needed to return a specific subset of photos. Default: `0`. |
| `count` | integer | Number of photos to return. Default: `10`. |
| `limit` | integer | Alias for `count`. |
| `rev` | boolean | Sort order: `1` — reverse chronological (oldest first), `0` — newest first. Default: `0`. |

### Result

In API version 5.0 and above, returns an object containing:
* `count` (integer) — total number of photos in the album;
* `items` (array) — array of photo objects.

In API versions below 5.0, returns an array where the first element is the total count, followed by photo objects (`[count, photo1, photo2, ...]`).

Each photo object contains:
| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Photo ID. |
| `pid` | integer | Photo ID (for backwards compatibility). |
| `owner_id` | integer | Photo owner ID. |
| `user_id` | integer | Photo owner ID (for backwards compatibility). |
| `album_id` | integer | Album ID (`-3` for unlisted/temp photos). |
| `aid` | integer | Album ID (for backwards compatibility). |
| `width` | integer | Original image width in pixels. |
| `height` | integer | Original image height in pixels. |
| `text` | string | Photo caption/description. |
| `date` | integer | Upload time (Unix timestamp). |
| `access_key` | string | Photo access key. |
| `photo_75` | string | 75x75 px copy URL. |
| `photo_130` | string | 130x130 px copy URL. |
| `photo_604` | string | Up to 604 px copy URL. |
| `photo_807` | string | Up to 807 px copy URL. |
| `photo_1280` | string | Up to 1280 px copy URL. |
| `photo_2560` | string | Up to 2560 px copy URL. |
| `url` | string | Direct URL of the original uploaded image file. |
| `orig_photo` | object | Information about original file (`height`, `width`, `type`, `url`). |
| `sizes` | array | Array of size copy objects (if `photo_sizes=1`). |
| `likes` | object | Likes information (`count`, `user_likes`, `can_like`, `can_publish`) — when `extended=1` or in API < 5.0. |
| `comments` | object | Comments information (`count`, `can_post`) — when `extended=1` or in API < 5.0. |
| `can_comment` | integer | `1` if the current user can comment on the photo. |
| `can_repost` | integer | `1` if the current user can share the photo. |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Photo access restricted by privacy settings. |
| `-78` | `Photos count must not exceed limit` — Exceeded limit of 78 IDs in `photo_ids`. |

### Example Request
```http
POST /method/photos.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&album_id=profile&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "pid": 1,
                "owner_id": 1,
                "user_id": 1,
                "album_id": 1,
                "aid": 1,
                "width": 800,
                "height": 600,
                "text": "My Photo",
                "date": 1609459200,
                "access_key": "",
                "photo_75": "https://openvk.instance/photos/1_1_cropped/miniscule.jpeg",
                "photo_130": "https://openvk.instance/photos/1_1_cropped/tiny.jpeg",
                "photo_604": "https://openvk.instance/photos/1_1_cropped/normal.jpeg",
                "url": "https://openvk.instance/photos/1_1.jpeg",
                "likes": {
                    "count": 12,
                    "user_likes": 0,
                    "can_like": 1,
                    "can_publish": 1
                },
                "comments": {
                    "count": 2,
                    "can_post": 1
                },
                "can_comment": 1,
                "can_repost": 1
            }
        ]
    }
}
```
