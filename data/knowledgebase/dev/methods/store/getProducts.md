OpenVK-KB-Heading: store.getProducts

# store.getProducts

Returns a list of digital goods store products (sticker packs) with filtering support by purchase or active status.

### Authorization
Does not require authorization unless filtering by `purchased` or `active` without specifying `user_id`.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `type` | string | Product type. Default: `stickers`. |
| `filters` | string | Comma-separated list of filters: `purchased`, `active`. |
| `extended` | integer | `1` — return detailed product information (previews, stickers list), `0` — minimal. Default: `1`. |
| `count` | integer | Number of items to return. Default: `50`. |
| `offset` | integer | Offset into the items list. Default: `0`. |
| `product_ids` | string | Comma-separated list of product IDs. |
| `user_id` | integer | User ID for whom products are queried. |

### Result

Returns an object containing:
* `count` (integer) — total number of products;
* `items` (array) — array of product objects.

Each product object contains:
| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Product (sticker pack) ID. |
| `type` | string | Product type (`stickers`). |
| `title` | string | Pack title. |
| `description` | string | Pack description. |
| `author` | string | Pack author or creator. |
| `purchased` | integer | `1` if purchased or obtained by user, `0` otherwise. |
| `active` | integer | `1` if active in quick access keyboard, `0` if hidden. |
| `price` | integer | Price in coins (`0` if free). |
| `price_str` | string | Formatted price string ("Free" or "N coins"). |
| `photo_128` | string | 128x128 px cover URL. |
| `photo_256` | string | 256x256 px cover URL. |
| `is_animated` | boolean | Whether the pack is animated. |
| `animation_url` | string/null | URL of animation file (Lottie/TGS JSON). |
| `stickers_count` | integer | Number of stickers in the pack. |
| `sticker_ids` | array | Array of sticker IDs. |
| `previews` | array | Previews of first stickers in the pack. |

### Example Request
```http
POST /method/store.getProducts HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

filters=purchased&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "type": "stickers",
                "title": "Peach the Cat",
                "name": "Peach the Cat",
                "description": "Cute cat",
                "author": "OpenVK",
                "purchased": 1,
                "active": 1,
                "price": 0,
                "price_str": "Free",
                "photo_128": "https://openvk.instance/images/stickers/1/128b.png",
                "photo_256": "https://openvk.instance/images/stickers/1/256b.png",
                "is_animated": false,
                "stickers_count": 24,
                "sticker_ids": [1, 2, 3]
            }
        ]
    }
}
```
