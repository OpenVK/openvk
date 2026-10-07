OpenVK-KB-Heading: Doc Object

# Doc Object

The **Doc** object describes a document / file in VK / OpenVK. The structure of the returned fields depends on the API version passed in the `v` parameter.

---

## API version 5.0 and higher (v >= 5.0)

API versions 5.0+ use the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Document ID. |
| `owner_id` | integer | Document owner ID. |
| `title` | string | Document title including extension. |
| `size` | integer | File size in bytes. |
| `ext` | string | File extension (e.g. `"pdf"`, `"png"`, `"zip"`). |
| `url` | string | Direct download URL. |
| `date` | integer | Upload timestamp (Unix timestamp). |
| `type` | integer | Document type (`1` — text, `2` — archive, `3` — GIF, `4` — image, `5` — audio, `6` — video, `7` — ebook, `8` — unknown). |
| `access_key` | string | Document access key. |
| `preview` | object | *(Optional)* Preview thumbnails object (`photo`, `sizes`). |
| `tags` | array | *(Optional)* Document tags. |

### Example Object (v >= 5.0)
```json
{
    "id": 12,
    "owner_id": 1,
    "title": "guide.pdf",
    "size": 1048576,
    "ext": "pdf",
    "url": "https://openvk.instance/docs/1_12.pdf",
    "date": 1696680000,
    "type": 1,
    "access_key": "abc123def456"
}
```

---

## Before API version 5.0 (v < 5.0)

In API versions 3.x and 4.x, legacy `did` is returned:

| Field | Type | Description |
| --- | --- | --- |
| `did` / `id` | integer | Document ID. |
| `owner_id` | integer | Document owner ID. |
| `title` | string | File name. |
| `size` | integer | File size in bytes. |
| `ext` | string | File extension. |
| `url` | string | File download URL. |
| `date` | integer | Creation timestamp (Unix timestamp). |

### Example Object (v < 5.0)
```json
{
    "did": 12,
    "owner_id": 1,
    "title": "guide.pdf",
    "size": 1048576,
    "ext": "pdf",
    "url": "https://openvk.instance/docs/1_12.pdf",
    "date": 1696680000
}
```
