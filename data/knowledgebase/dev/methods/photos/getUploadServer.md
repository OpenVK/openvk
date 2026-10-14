OpenVK-KB-Heading: photos.getUploadServer

# photos.getUploadServer

Returns the server upload URL for uploading photos to a user or community photo album.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `album_id` | integer | ID of the photo album where photos will be uploaded. |

### Result

Returns an object containing:
| Field | Type | Description |
| --- | --- | --- |
| `upload_url` | string | URL for uploading photo files (via HTTP POST multipart/form-data with field `photo`). |
| `album_id` | integer | Album ID (if provided). |
| `user_id` | integer | Current user ID. |

> **Note:** After sending file(s) to the returned `upload_url`, the upload server responds with JSON containing `photos_list`, `album_id`, and `hash`. These parameters must be passed to [photos.save](/dev/methods/photos/save) to complete the upload process.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Example Request
```http
POST /method/photos.getUploadServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

album_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/a1b2c3d4e5f6?abc123pack...",
        "album_id": 1,
        "user_id": 1
    }
}
```
