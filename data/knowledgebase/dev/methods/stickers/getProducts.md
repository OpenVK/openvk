OpenVK-KB-Heading: stickers.getProducts

# stickers.getProducts

Returns a list of sticker products with filtering. Alias for [store.getProducts](/dev/methods/store/getProducts).

### Authorization
Does not require authorization unless querying `purchased` or `active` filters.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `type` | string | Product type. Default: `stickers`. |
| `filters` | string | Comma-separated filters: `active`, `purchased`. |
| `extended` | integer | `1` — return detailed product information, `0` — minimal. Default: `1`. |
| `count` | integer | Number of items to return. Default: `50`. |
| `offset` | integer | Offset into the items list. Default: `0`. |
| `product_ids` | string/array | Comma-separated list of product IDs. |
| `user_id` | integer | User ID for whom products are queried. |

### Result

Returns products list in the format identical to [store.getProducts](/dev/methods/store/getProducts).

### Example Request
```http
POST /method/stickers.getProducts HTTP/1.1
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
                "price": 0,
                "purchased": 1,
                "active": 1
            }
        ]
    }
}
```
