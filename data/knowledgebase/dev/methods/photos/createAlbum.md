OpenVK-KB-Heading: photos.createAlbum

# photos.createAlbum

Creates a new empty photo album for the current user or in a managed community.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `title` | string | **Required.** Photo album title. |
| `group_id` | integer | ID of the community in which the album is being created. If omitted or `0`, the album is created for the current user. |
| `description` | string | Photo album description. Default is empty. |

### Result

Returns the created photo album object:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | ID of the created album. |
| `aid` | integer | Album ID (for backwards compatibility). |
| `thumb_id` | integer | Cover photo ID (`0` if album is empty). |
| `owner_id` | integer | Album owner ID (positive for user, negative for community). |
| `title` | string | Album title. |
| `description` | string | Album description. |
| `created` | integer | Creation time (Unix timestamp). |
| `updated` | integer | Last update time (Unix timestamp). |
| `size` | integer | Number of photos in the album (`0` upon creation). |
| `privacy` | integer | Privacy level. |
| `privacy_comment` | integer | Comments privacy level. |
| `upload_by_admins_only` | integer | `1` if only admins can upload photos. |
| `comments_disabled` | integer | `0` if comments are enabled, `1` if disabled. |
| `can_upload` | integer | `1` if current user can upload photos to the album. |
| `thumb_src` | string | Default cover image URL. |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — No permission to create albums in the specified community. |

### Example Request
```http
POST /method/photos.createAlbum HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

title=My%20New%20Album&description=Vacation%20photos&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "id": 1,
        "aid": 1,
        "thumb_id": 0,
        "owner_id": 1,
        "title": "My New Album",
        "description": "Vacation photos",
        "created": 1609459200,
        "updated": 1609459200,
        "size": 0,
        "privacy": 0,
        "privacy_comment": 1,
        "upload_by_admins_only": 1,
        "comments_disabled": 0,
        "can_upload": 1,
        "thumb_src": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
    }
}
```
