OpenVK-KB-Heading: photos.getById

# photos.getById

Returns information about photos by their IDs.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `photos` | string | **Required.** Comma-separated list of photo IDs in format `owner_id_photo_id` or `owner_id_photo_id_access_key` (maximum 78). |
| `extended` | boolean | `1` — return extra fields (`likes`, `comments`, `can_comment`, `can_repost`), `0` — do not return. Default: `0`. |
| `photo_sizes` | boolean | `1` — return array of sizes in `sizes`, `0` — do not return. Default: `0`. |

### Result

Returns an array of photo objects (`[photo1, photo2, ...]`). Inaccessible or deleted photos are omitted.

Each photo object contains:
| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Photo ID. |
| `pid` | integer | Photo ID (for backwards compatibility). |
| `owner_id` | integer | Photo owner ID. |
| `user_id` | integer | Photo owner ID (for backwards compatibility). |
| `album_id` | integer | Photo album ID. |
| `aid` | integer | Album ID (for backwards compatibility). |
| `width` | integer | Image width in pixels. |
| `height` | integer | Image height in pixels. |
| `text` | string | Photo caption/description. |
| `date` | integer | Upload time (Unix timestamp). |
| `access_key` | string | Photo access key. |
| `photo_75` | string | 75x75 px copy URL. |
| `photo_130` | string | 130x130 px copy URL. |
| `photo_604` | string | Up to 604 px copy URL. |
| `photo_807` | string | Up to 807 px copy URL. |
| `photo_1280` | string | Up to 1280 px copy URL. |
| `photo_2560` | string | Up to 2560 px copy URL. |
| `url` | string | Direct URL of original file. |
| `orig_photo` | object | Information about original file. |
| `sizes` | array | Array of sizes (if `photo_sizes=1`). |
| `likes` | object | Likes object (if `extended=1` or in API < 5.0). |
| `comments` | object | Comments object (if `extended=1` or in API < 5.0). |
| `can_comment` | integer | `1` if the current user can comment on the photo. |
| `can_repost` | integer | `1` if the current user can share the photo. |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `-78` | `Photos count must not exceed limit` — Exceeded limit of 78 IDs in `photos`. |

### Example Request
```http
POST /method/photos.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

photos=1_1,1_2&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": [
        {
            "id": 1,
            "pid": 1,
            "owner_id": 1,
            "user_id": 1,
            "album_id": 1,
            "aid": 1,
            "width": 1280,
            "height": 720,
            "text": "Photo from the walk",
            "date": 1609459200,
            "access_key": "",
            "photo_75": "https://openvk.instance/photos/1_1_cropped/miniscule.jpeg",
            "photo_130": "https://openvk.instance/photos/1_1_cropped/tiny.jpeg",
            "photo_604": "https://openvk.instance/photos/1_1_cropped/normal.jpeg",
            "url": "https://openvk.instance/photos/1_1.jpeg",
            "likes": {
                "count": 5,
                "user_likes": 0,
                "can_like": 1,
                "can_publish": 1
            },
            "comments": {
                "count": 0,
                "can_post": 1
            },
            "can_comment": 1,
            "can_repost": 1
        }
    ]
}
```
