OpenVK-KB-Heading: stickers.getFrom

# stickers.getFrom

Returns detailed information about a specific sticker pack and its full list of stickers.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `stickerpack_id` | integer | **Required.** Sticker pack ID. |

### Result

Returns the sticker pack object:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Sticker pack ID. |
| `name` | string | Pack name. |
| `title` | string | Pack title. |
| `description` | string | Pack description. |
| `slug` | string | Pack slug. |
| `price` | integer | Price in coins. |
| `end_time` | integer | Expiration timestamp or `0`. |
| `purchased` | integer | `1` if purchased/installed by user, `0` otherwise. |
| `photo_128` | string | 128x128 px cover URL. |
| `photo_256` | string | 256x256 px cover URL. |
| `is_animated` | boolean | Whether the pack is animated. |
| `animation_url` | string/null | URL of animation file (Lottie/TGS JSON). |
| `stickers_count` | integer | Number of stickers. |
| `sticker_ids` | array | Array of sticker IDs. |
| `stickers` | array | Array of sticker objects. |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Sticker pack not found` / `Access denied` — Sticker pack not found, deleted, or unavailable. |

### Example Request
```http
POST /method/stickers.getFrom HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

stickerpack_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "id": 1,
        "name": "Peach the Cat",
        "title": "Peach the Cat",
        "description": "Cute ginger cat",
        "slug": "peach",
        "price": 0,
        "end_time": 0,
        "purchased": 1,
        "photo_128": "https://openvk.instance/images/stickers/1/128b.png",
        "photo_256": "https://openvk.instance/images/stickers/1/256b.png",
        "is_animated": false,
        "animation_url": null,
        "stickers_count": 24,
        "sticker_ids": [1, 2, 3],
        "stickers": [
            {
                "sticker_id": 1,
                "is_allowed": true
            }
        ]
    }
}
```
