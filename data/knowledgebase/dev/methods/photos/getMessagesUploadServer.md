OpenVK-KB-Heading: photos.getMessagesUploadServer

# photos.getMessagesUploadServer

Returns the server upload URL for uploading a photo to a private message or group chat.

### Authorization
Requires user authorization (`access_token`) with the `photos` or `messages` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination conversation or dialog ID. Default: `0`. |
| `group_id` | integer | Community ID (if uploading on behalf of a group). Default: `0`. |

### Result

Returns an object containing the `upload_url` field:
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/msg_hash...?info..."
    }
}
```

> **Note:** After performing a POST request with the image file (in the `photo` field) to the upload URL, the server returns `photo`, `server`, and `hash` fields, which must be passed to [photos.saveMessagesPhoto](/dev/methods/photos/saveMessagesPhoto).

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Example Request
```http
POST /method/photos.getMessagesUploadServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/msg_hash123?info..."
    }
}
```
