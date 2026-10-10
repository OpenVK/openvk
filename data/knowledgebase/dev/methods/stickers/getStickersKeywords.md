OpenVK-KB-Heading: stickers.getStickersKeywords

# stickers.getStickersKeywords

Returns dictionary of sticker suggestions by emoji and keyword aliases for autocomplete in message input. Alias for [store.getStickersKeywords](/dev/methods/store/getStickersKeywords).

### Authorization
Does not require authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `aliases` | integer | `1` — include text aliases for emojis (e.g. "hi" for "👋"), `0` — exact emoji matches only. Default: `1`. |
| `all_products` | integer | `1` — return suggestions from all available packs, `0` — only from active user packs. Default: `1`. |
| `need_stickers` | integer | `1` — include full sticker objects, `0` — sticker IDs only. Default: `1`. |
| `stickers_hash` | string | Stickers dictionary hash for client caching. |
| `products_hash` | string | Products hash for client caching. |
| `count` | integer | Max records count. Default: `0` (all). |
| `user_id` | integer | User ID. |

### Result

Returns suggestion structure identical to [store.getStickersKeywords](/dev/methods/store/getStickersKeywords).

### Example Request
```http
POST /method/stickers.getStickersKeywords HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

aliases=1&need_stickers=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 1,
        "dictionary": [
            {
                "words": ["👋", "hi", "hello"],
                "user_stickers": [
                    {
                        "id": 1,
                        "sticker_id": 1
                    }
                ],
                "promoted_stickers": []
            }
        ],
        "base_url": "https://openvk.instance/images/stickers/",
        "stickers_hash": "d41d8cd98f00b204e9800998ecf8427e",
        "products_hash": "7b8b965ad4bca0e41ab51de7b31363a1"
    }
}
```
