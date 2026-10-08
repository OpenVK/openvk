OpenVK-KB-Heading: photos.getUserPhotos

# photos.getUserPhotos

Returns a list of all photos of a user in reverse chronological order. This method is an alias for [photos.getAll](/dev/methods/photos/getAll).

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | User ID. If not specified, `owner_id` is used. |
| `owner_id` | integer | Owner ID (alternative to `user_id`). |
| `extended` | boolean | `1` — return extra fields (`likes`, `comments`, `can_comment`, `can_repost`), `0` — do not return. Default: `0`. |
| `offset` | integer | Offset needed to return a specific subset of photos. Default: `0`. |
| `count` | integer | Number of photos to return. Default: `100`. |
| `photo_sizes` | boolean | `1` — return sizes in `sizes` array, `0` — do not return. Default: `0`. |

### Result

Returns the photos list in the same structure as [photos.getAll](/dev/methods/photos/getAll).

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Access to user photos is restricted by privacy settings. |

### Example Request
```http
POST /method/photos.getUserPhotos HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&count=20&access_token=YOUR_ACCESS_TOKEN&v=5.138
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
                "text": "Photo",
                "date": 1609459200,
                "access_key": "",
                "photo_75": "https://openvk.instance/photos/1_1_cropped/miniscule.jpeg",
                "photo_130": "https://openvk.instance/photos/1_1_cropped/tiny.jpeg",
                "photo_604": "https://openvk.instance/photos/1_1_cropped/normal.jpeg",
                "url": "https://openvk.instance/photos/1_1.jpeg"
            }
        ]
    }
}
```
