OpenVK-KB-Heading: photos.getWallUploadServer

# photos.getWallUploadServer

Returns the server upload URL for uploading a photo to a user or community wall.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | ID of the community on whose wall the photo will be posted. If omitted, photo is uploaded for the current user's wall. |

### Result

Returns an object containing:
| Field | Type | Description |
| --- | --- | --- |
| `upload_url` | string | URL for uploading the photo file via HTTP POST (field `photo`). |
| `album_id` | integer | Wall photos album ID. |
| `user_id` | integer | Current user ID. |

> **Note:** After uploading the photo to `upload_url`, the server returns `photo`, `server`, and `hash` fields, which must be passed to [photos.saveWallPhoto](/dev/methods/photos/saveWallPhoto).

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `200` | `Access: Club can't be 'written' by user` — No permission to post on community wall. |
| `404` | `Club not found` — Specified community was not found. |

### Example Request
```http
POST /method/photos.getWallUploadServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/wall_hash...?info...",
        "album_id": 2,
        "user_id": 1
    }
}
```
