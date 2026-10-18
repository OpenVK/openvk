OpenVK-KB-Heading: photos.deleteAlbum

# photos.deleteAlbum

Deletes a photo album along with all photos contained within it.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `album_id` | integer | **Required.** ID of the album to delete. |
| `group_id` | integer | ID of the community (optional). |

> **Note:** System albums (avatars, wall photos) cannot be deleted.

### Result

Returns `1` upon successful deletion.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Album not found, already deleted, is a system album, or user lacks permission. |

### Example Request
```http
POST /method/photos.deleteAlbum HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

album_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
