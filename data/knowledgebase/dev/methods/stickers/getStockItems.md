OpenVK-KB-Heading: stickers.getStockItems

# stickers.getStockItems

Returns showcase catalog sticker items. Alias for [store.getStockItems](/dev/methods/store/getStockItems).

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

Returns showcase items list in the format identical to [store.getStockItems](/dev/methods/store/getStockItems).

### Example Request
```http
POST /method/stickers.getStockItems HTTP/1.1
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
                    "title": "Peach the Cat",
                    "price": 0
                },
                "price": 0,
                "price_str": "Free",
                "can_purchase": 1,
                "free": 1
            }
        ]
    }
}
```
