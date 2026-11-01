OpenVK-KB-Heading: photos.editAlbum

# photos.editAlbum

Edits the title and description of an existing photo album.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `album_id` | integer | **Required.** ID of the album to edit. |
| `owner_id` | integer | **Required.** ID of the album owner (positive for user, negative for community). |
| `title` | string | New title for the album. |
| `description` | string | New description for the album. |
| `privacy` | integer | Privacy level. Default: `0`. |

> **Note:** System albums (avatars, wall photos, saved photos) cannot be edited via this method.

### Result

Returns `1` on success.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — No permission to edit the album. |
| `114` | `Invalid album id` — Album not found, deleted, or is a system album. |

### Example Request
```http
POST /method/photos.editAlbum HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

album_id=1&owner_id=1&title=Updated%20Album&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
