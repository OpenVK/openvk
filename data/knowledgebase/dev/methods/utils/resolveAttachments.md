OpenVK-KB-Heading: utils.resolveAttachments

# utils.resolveAttachments

Parses a comma-separated list of attachments into an array of standard VK API attachment objects.

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `attachments` | string | **Required.** Comma-separated list of attachments in `<type><owner>_<id>` format (e.g. `photo1_23,video-5_10,audio1_45`). |
| `allow_type` | integer | Allowed types filter: `0` — all supported types (`photo`, `video`, `doc`, `audio`, `wall`, `sticker`, `gift`), `1` — note-enabled mode (`photo`, `video`, `note`, `audio`, `sticker`, `gift`). Default: `0`. |

### Result

Returns an array of attachment objects. Each element contains a `type` field and the corresponding data structure (e.g., `photo`, `video`, `audio`). If an item is inaccessible or restricted by privacy, an object with `type: "unknown"` is returned.

### Example Request
```http
POST /method/utils.resolveAttachments HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

attachments=photo1_12,audio1_10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": [
        {
            "type": "photo",
            "photo": {
                "id": 12,
                "owner_id": 1,
                "album_id": -6,
                "text": "Beautiful sunset",
                "date": 1775650000,
                "sizes": [
                    {
                        "type": "m",
                        "url": "https://openvk.instance/photos/1_12_m.jpg",
                        "width": 320,
                        "height": 240
                    }
                ]
            }
        },
        {
            "type": "audio",
            "audio": {
                "id": 10,
                "owner_id": 1,
                "artist": "Viktor Tsoi",
                "title": "Gruppa krovi",
                "duration": 285,
                "url": "https://openvk.instance/audio/1_10.mp3"
            }
        }
    ]
}
```
