OpenVK-KB-Heading: Note Object

# Note Object

The **Note** object describes a user text note in VK / OpenVK. The structure of the returned fields depends on the API version passed in the `v` parameter.

---

## API version 5.0 and higher (v >= 5.0)

API versions 5.0+ use the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Note ID. |
| `owner_id` | integer | Note author/owner ID. |
| `title` | string | Note title. |
| `text` | string | Full text of the note. |
| `date` | integer | Creation timestamp (Unix timestamp). |
| `comments` | integer | Number of comments on the note. |
| `view_url` | string | URL for viewing the note on the website. |

### Example Object (v >= 5.0)
```json
{
    "id": 1,
    "owner_id": 1,
    "title": "My first note",
    "text": "Note text content in OpenVK",
    "date": 1696680000,
    "comments": 3,
    "view_url": "/note1_1"
}
```

---

## Before API version 5.0 (v < 5.0)

In API versions 3.x and 4.x, legacy `nid` is returned:

| Field | Type | Description |
| --- | --- | --- |
| `nid` / `id` | integer | Note ID. |
| `owner_id` | integer | Owner ID. |
| `title` | string | Note title. |
| `text` | string | Note text. |
| `date` | integer | Creation timestamp (Unix timestamp). |
| `comments` | integer | Comments count. |

### Example Object (v < 5.0)
```json
{
    "nid": 1,
    "owner_id": 1,
    "title": "My first note",
    "text": "Note text content in OpenVK",
    "date": 1696680000,
    "comments": 3
}
```
