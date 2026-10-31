OpenVK-KB-Heading: stickers.getAll

# stickers.getAll

Returns all available sticker packs from the catalog.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `count` | integer | Number of sticker packs to return. Default: `50`. |
| `offset` | integer | Offset needed to return a specific subset of packs. Default: `0`. |

### Result

Returns an object containing:
* `count` (integer) — total number of sticker packs in catalog;
* `items` (array) — array of sticker pack objects.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Example Request
```http
POST /method/stickers.getAll HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=20&offset=0&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 5,
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
                "sticker_ids": [1, 2, 3]
            }
        ]
    }
}
```
