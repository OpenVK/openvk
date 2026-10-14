OpenVK-KB-Heading: photos.getOwnerPhotoUploadServer

# photos.getOwnerPhotoUploadServer

Returns the server upload URL for uploading the main profile or community photo (avatar).

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Avatar owner ID (positive number or `0` for current user, negative for community). Default: `0`. |

### Result

Returns an object containing the `upload_url` field:
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/hash...?info..."
    }
}
```

> **Note:** After performing a POST request to the upload URL, the server returns `photo` and `hash` fields, which must then be passed to [photos.saveOwnerPhoto](/dev/methods/photos/saveOwnerPhoto).

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `200` | `Access: Club can't be 'written' by user` — No administrator permissions to change community avatar. |
| `404` | `Club not found` — Specified community was not found. |

### Example Request
```http
POST /method/photos.getOwnerPhotoUploadServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=-1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/a1b2c3d4e5f6?info..."
    }
}
```
