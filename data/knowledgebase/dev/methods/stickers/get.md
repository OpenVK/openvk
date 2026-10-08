OpenVK-KB-Heading: stickers.get

# stickers.get

Returns active sticker packs of the current user along with stickers in each pack.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | User ID. Default: current user ID. |
| `count` | integer | Number of sticker packs to return. Default: `50`. |
| `offset` | integer | Offset needed to return a specific subset of packs. Default: `0`. |

### Result

Returns an object containing:
* `count` (integer) — total number of active sticker packs;
* `items` (array) — array of sticker pack objects.

Each sticker pack object contains:
| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Sticker pack ID. |
| `name` | string | Sticker pack name. |
| `title` | string | Sticker pack title. |
| `description` | string | Sticker pack description. |
| `slug` | string | Sticker pack slug. |
| `price` | integer | Price in coins (`0` if free). |
| `end_time` | integer | Expiration time (Unix timestamp) or `0`. |
| `purchased` | integer | `1` if purchased/installed, `0` otherwise. |
| `photo_128` | string | 128x128 px cover URL. |
| `photo_256` | string | 256x256 px cover URL. |
| `is_animated` | boolean | Whether the sticker pack is animated. |
| `animation_url` | string/null | URL of Lottie/TGS animation JSON file. |
| `stickers_count` | integer | Number of stickers in the pack. |
| `sticker_ids` | array | Array of sticker IDs. |
| `stickers` | array | Array of sticker objects in the pack. |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Example Request
```http
POST /method/stickers.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=20&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 1,
        "items": [
            {
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
        ]
    }
}
```
