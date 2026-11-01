OpenVK-KB-Heading: Album Object

# Album Object

The **Album** object describes a user or community photo album in VK / OpenVK. The structure of the returned fields depends on the API version passed in the `v` parameter.

---

## API version 5.0 and higher (v >= 5.0)

API versions 5.0+ use the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Photo album ID. |
| `thumb_id` | integer | Cover photo ID. |
| `owner_id` | integer | Album owner ID. |
| `title` | string | Photo album title. |
| `description` | string | Photo album description. |
| `created` | integer | Album creation timestamp (Unix timestamp). |
| `updated` | integer | Album last update timestamp (Unix timestamp). |
| `size` | integer | Number of photos in the album. |
| `thumb_src` | string | Cover photo URL. |
| `can_upload` | integer | `1` if current user can upload photos to the album. |
| `sizes` | array | *(Optional)* Cover copies of various sizes (when `need_covers=1, photo_sizes=1`). |

### Example Object (v >= 5.0)
```json
{
    "id": 1,
    "thumb_id": 100,
    "owner_id": 1,
    "title": "Summer photos",
    "description": "Trip to the seaside",
    "created": 1696680000,
    "updated": 1696683600,
    "size": 15,
    "thumb_src": "https://openvk.instance/photos/130/1_100.jpg",
    "can_upload": 1
}
```

---

## Before API version 5.0 (v < 5.0)

In API versions 3.x and 4.x, legacy `aid` is returned:

| Field | Type | Description |
| --- | --- | --- |
| `aid` / `id` | integer | Album ID (equivalent to `id` in API v5.0+). |
| `thumb_id` | integer | Cover photo ID. |
| `owner_id` | integer | Owner ID. |
| `title` | string | Album title. |
| `description` | string | Description. |
| `created` | integer | Creation timestamp. |
| `updated` | integer | Update timestamp. |
| `size` | integer | Photo count. |
| `thumb_src` | string | Cover URL. |

### Example Object (v < 5.0)
```json
{
    "aid": 1,
    "thumb_id": 100,
    "owner_id": 1,
    "title": "Summer photos",
    "description": "Trip to the seaside",
    "created": 1696680000,
    "updated": 1696683600,
    "size": 15,
    "thumb_src": "https://openvk.instance/photos/130/1_100.jpg"
}
```
