OpenVK-KB-Heading: photos.saveWallPhoto

# photos.saveWallPhoto

Saves a photo for subsequent attachment to a wall post after uploading via [photos.getWallUploadServer](/dev/methods/photos/getWallUploadServer).

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `photo` | string | **Required.** Upload information string received from the upload server. |
| `hash` | string | **Required.** Hash signature received from the upload server. |
| `group_id` | integer | Community ID if the photo was uploaded for a community wall. Default: `0`. |
| `caption` | string | Photo caption/description. |
| `server` | integer | Upload server number (compatibility parameter). |
| `user_id` | integer | User ID (compatibility parameter). |
| `wallpost` | integer | Compatibility parameter. Default: `1`. |

### Result

Returns an array containing the saved photo object:

```json
{
    "response": [
        {
            "id": 5,
            "pid": 5,
            "owner_id": 1,
            "user_id": 1,
            "album_id": 2,
            "aid": 2,
            "width": 1280,
            "height": 960,
            "text": "Wall photo",
            "date": 1609459200,
            "access_key": "",
            "photo_75": "https://openvk.instance/photos/1_5_cropped/miniscule.jpeg",
            "photo_130": "https://openvk.instance/photos/1_5_cropped/tiny.jpeg",
            "photo_604": "https://openvk.instance/photos/1_5_cropped/normal.jpeg",
            "url": "https://openvk.instance/photos/1_5.jpeg"
        }
    ]
}
```

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `8` | `group_id doesn't match` — Passed `group_id` does not match the upload session. |
| `10` | `Invalid image` — Image file not found in temporary storage. |
| `121` | `Incorrect hash` — Invalid upload hash signature. |
| `129` | `Invalid image file` — Error processing image file. |

### Example Request
```http
POST /method/photos.saveWallPhoto HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=0&photo=1|xyz789|0&hash=d8a1c9e...&caption=Wall%20photo&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": [
        {
            "id": 5,
            "pid": 5,
            "owner_id": 1,
            "user_id": 1,
            "album_id": 2,
            "aid": 2,
            "width": 1280,
            "height": 960,
            "text": "Wall photo",
            "date": 1609459200,
            "access_key": "",
            "photo_75": "https://openvk.instance/photos/1_5_cropped/miniscule.jpeg",
            "photo_130": "https://openvk.instance/photos/1_5_cropped/tiny.jpeg",
            "photo_604": "https://openvk.instance/photos/1_5_cropped/normal.jpeg",
            "url": "https://openvk.instance/photos/1_5.jpeg"
        }
    ]
}
```
