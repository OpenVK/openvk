OpenVK-KB-Heading: store.getStockItems

# store.getStockItems

Returns showcase catalog items of the digital store (sticker packs catalog) divided by categories.

### Authorization
Does not require authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `type` | string | Product type. Default: `stickers`. |
| `section` | string | Showcase section: `popular`, `free`, `all`, `catalog`. Default: `popular`. |
| `extended` | integer | `1` — return detailed product information, `0` — minimal. Default: `1`. |
| `count` | integer | Number of items to return. Default: `50`. |
| `offset` | integer | Offset into the items list. Default: `0`. |
| `merchant` | string | Merchant identifier (compatibility parameter). |

### Result

Returns an object containing:
* `count` (integer) — total number of items in the chosen section;
* `items` (array) — array of showcase item objects.

Each element in the `items` array contains:
| Field | Type | Description |
| --- | --- | --- |
| `product` | object | Product (sticker pack) object. |
| `description` | string | Pack description. |
| `author` | string | Pack author. |
| `price` | integer | Price in coins. |
| `price_str` | string | Formatted price string. |
| `can_purchase` | integer | `1` if item is purchasable. |
| `free` | integer | `1` if item is free, `0` otherwise. |
| `is_new` | integer | `1` if added less than 30 days ago, `0` otherwise. |

### Example Request
```http
POST /method/store.getStockItems HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

section=free&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "product": {
                    "id": 1,
                    "type": "stickers",
                    "title": "Peach the Cat",
                    "name": "Peach the Cat",
                    "price": 0,
                    "purchased": 1,
                    "active": 1
                },
                "description": "Cute ginger cat",
                "author": "OpenVK",
                "price": 0,
                "price_str": "Free",
                "can_purchase": 1,
                "free": 1,
                "is_new": 0
            }
        ]
    }
}
```
