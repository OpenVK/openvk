OpenVK-KB-Heading: Photo Object

# Photo Object

The **Photo** object describes an image or photo attachment in OpenVK / VKontakte. The return structure depends on the API version.

---

## API Version 5.77 and Higher (v >= 5.77)

Starting with API version 5.77, image copies are returned in a single `sizes` array:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Photo ID. |
| `owner_id` | integer | Photo owner ID (user or community ID). |
| `album_id` | integer | Album ID (`0` for wall photos, `-3` for saved). |
| `width` | integer | Original photo width in pixels. |
| `height` | integer | Original photo height in pixels. |
| `text` | string | Description text of the photo. |
| `date` | integer | Upload timestamp (Unix time). |
| `access_key` | string | Photo access key for private photos and attachments. |
| `sizes` | array | Array of size variant objects (`type`, `url`, `width`, `height`). |
| `orig_photo` | object | Metadata and URL for the original uploaded image file. |

### JSON Example (v >= 5.77)
```json
{
    "id": 100,
    "owner_id": 1,
    "album_id": 0,
    "width": 1920,
    "height": 1080,
    "text": "Sunset view",
    "date": 1696680000,
    "access_key": "abc123def456",
    "sizes": [
        {
            "type": "s",
            "url": "https://openvk.instance/photos/75/1_100.jpg",
            "width": 75,
            "height": 42
        },
        {
            "type": "m",
            "url": "https://openvk.instance/photos/130/1_100.jpg",
            "width": 130,
            "height": 73
        },
        {
            "type": "x",
            "url": "https://openvk.instance/photos/604/1_100.jpg",
            "width": 604,
            "height": 340
        }
    ]
}
```

---

## Below API Version 5.77 (v < 5.77)

In API versions below 5.77 (including v3.x, v4.x, and v5.0–v5.76), image URLs are provided in separate fields:

| Field | Type | Description |
| --- | --- | --- |
| `pid` / `id` | integer | Photo ID. |
| `aid` / `album_id` | integer | Album ID. |
| `owner_id` / `user_id` | integer | Owner ID. |
| `src_small` / `photo_75` | string | 75x75px image URL. |
| `src` / `photo_130` | string | 130x130px image URL. |
| `src_big` / `photo_604` | string | 604x604px image URL. |
| `src_xbig` / `photo_807` | string | 807x807px image URL. |
| `src_xxbig` / `photo_1280` | string | 1280x1280px image URL. |
| `src_xxxbig` / `photo_2560` | string | 2560x2560px image URL. |
| `src_original` | string | Highest quality original image URL. |
| `created` / `date` | integer | Timestamp (Unix time). |
| `text` | string | Description text. |

### JSON Example (v < 5.77)
```json
{
    "pid": 100,
    "aid": 0,
    "owner_id": 1,
    "user_id": 1,
    "src_small": "https://openvk.instance/photos/75/1_100.jpg",
    "src": "https://openvk.instance/photos/130/1_100.jpg",
    "src_big": "https://openvk.instance/photos/604/1_100.jpg",
    "created": 1696680000,
    "text": "Sunset view"
}
```
