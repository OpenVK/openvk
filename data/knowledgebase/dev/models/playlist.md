OpenVK-KB-Heading: Playlist Object

# Playlist Object

The **Playlist** object describes an audio playlist (album) in VK / OpenVK. The structure of the returned fields depends on the API version passed in the `v` parameter.

---

## API version 5.0 and higher (v >= 5.0)

API versions 5.0+ use the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Playlist ID. |
| `owner_id` | integer | Playlist owner ID (user or community). |
| `title` | string | Playlist title. |
| `description` | string | Playlist description. |
| `size` | integer | Number of audio tracks in the playlist. |
| `length` | integer | Total playlist length in seconds. |
| `created` | integer | Creation timestamp (Unix timestamp). |
| `modified` | integer\|null | Last modification timestamp (Unix timestamp). |
| `accessible` | boolean | Accessible for viewing by the current user. |
| `editable` | boolean | Editable by the current user. |
| `bookmarked` | boolean | Bookmarked/added by current user. |
| `listens` | integer | Total playlist listens count. |
| `cover_url` | string | Cover image URL. |
| `searchable` | boolean | Searchable flag. |
| `thumb` | object | *(Optional)* Cover thumbnails object (`photo_34`, `photo_68`, `photo_135`, `photo_270`, `photo_300`, `photo_600`, `photo_1200`). |

### Example Object (v >= 5.0)
```json
{
    "id": 1,
    "owner_id": 1,
    "title": "Favorite Tracks",
    "description": "Best hits",
    "size": 10,
    "length": 1840,
    "created": 1696680000,
    "accessible": true,
    "editable": true,
    "bookmarked": false,
    "listens": 120,
    "cover_url": "https://openvk.instance/photos/covers/1.jpg"
}
```

---

## Before API version 5.0 (v < 5.0)

In API versions 3.x and 4.x, basic audio album fields are returned:

| Field | Type | Description |
| --- | --- | --- |
| `album_id` / `id` | integer | Playlist ID. |
| `owner_id` | integer | Owner ID. |
| `title` | string | Album title. |

### Example Object (v < 5.0)
```json
{
    "album_id": 1,
    "owner_id": 1,
    "title": "Favorite Tracks"
}
```
