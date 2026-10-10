OpenVK-KB-Heading: photos.save

# photos.save

Saves photos after they have been successfully uploaded to the server via the URL obtained by [photos.getUploadServer](/dev/methods/photos/getUploadServer).

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `photos_list` | string | **Required.** JSON string with list of uploaded images received from the upload server. |
| `hash` | string | **Required.** Hash signature received from the upload server. |
| `album_id` | integer | Target album ID. Default: `0` (save without album). |
| `caption` | string | Caption/description for saved photos. |

### Result

Returns an object containing:
* `count` (integer) — number of successfully saved photos;
* `items` (array) — array of saved photo objects.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access: Album can't be 'written' by user` — No permission to upload photos to the specified album. |
| `121` | `Incorrect hash` — Invalid upload hash signature. |
| `129` | `Invalid image file` — Error processing uploaded image file. |
| `404` | `Invalid album` — Specified album was not found. |

### Example Request
```http
POST /method/photos.save HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

album_id=1&photos_list=[{"keyholder":"1","resource":"a1b2c3"}]&hash=d8a1c9e...&access_token=YOUR_ACCESS_TOKEN&v=5.138
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
                "width": 1920,
                "height": 1080,
                "text": "Uploaded photo",
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
