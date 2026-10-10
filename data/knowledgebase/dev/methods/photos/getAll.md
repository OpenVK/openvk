OpenVK-KB-Heading: photos.getAll

# photos.getAll

Returns all photos of a user or community in reverse chronological order.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** ID of the photos owner (positive for user, negative for community). |
| `extended` | boolean | `1` — return extra fields (`likes`, `comments`, `can_comment`, `can_repost`), `0` — do not return. Default: `0`. |
| `offset` | integer | Offset needed to return a specific subset of photos. Default: `0`. |
| `count` | integer | Number of photos to return. Default: `100`. |
| `photo_sizes` | boolean | `1` — return sizes in `sizes` array, `0` — do not return. Default: `0`. |

### Result

In API version 5.0 and above, returns an object containing:
* `count` (integer) — total number of photos;
* `items` (array) — array of photo objects.

In API versions below 5.0, returns an array starting with the count followed by photo objects (`[count, photo1, photo2, ...]`).

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Access to photos is restricted by privacy settings. |

### Example Request
```http
POST /method/photos.getAll HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&count=20&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 2,
        "items": [
            {
                "id": 2,
                "pid": 2,
                "owner_id": 1,
                "user_id": 1,
                "album_id": 1,
                "aid": 1,
                "width": 1024,
                "height": 768,
                "text": "New photo",
                "date": 1609460000,
                "access_key": "",
                "photo_75": "https://openvk.instance/photos/1_2_cropped/miniscule.jpeg",
                "photo_130": "https://openvk.instance/photos/1_2_cropped/tiny.jpeg",
                "photo_604": "https://openvk.instance/photos/1_2_cropped/normal.jpeg",
                "url": "https://openvk.instance/photos/1_2.jpeg"
            }
        ]
    }
}
```
