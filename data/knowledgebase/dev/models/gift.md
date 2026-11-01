OpenVK-KB-Heading: Gift Object

# Gift Object

The **Gift** object describes a sent gift to a user in VK / OpenVK.

---

## Sent Gift Object

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Gift record ID. |
| `from_id` | integer | Sender user ID (if not fully anonymous). |
| `message` | string | Text wish / comment attached to the gift. |
| `date` | integer | Send timestamp (Unix timestamp). |
| `privacy` | integer | Privacy level: `0` — public (visible to everyone), `1` — name hidden (visible only to recipient), `2` — anonymous. |
| `gift` | object | Gift graphic asset object (`id`, `thumb_48`, `thumb_96`, `thumb_256`). |

### Example Gift Object
```json
{
    "id": 1,
    "from_id": 2,
    "message": "Happy Birthday!",
    "date": 1696680000,
    "privacy": 0,
    "gift": {
        "id": 5,
        "thumb_48": "https://openvk.instance/images/gifts/5/48.png",
        "thumb_96": "https://openvk.instance/images/gifts/5/96.png",
        "thumb_256": "https://openvk.instance/images/gifts/5/256.png"
    }
}
```
