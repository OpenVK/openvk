OpenVK-KB-Heading: store.getStickersKeywords

# store.getStickersKeywords

Returns dictionary mapping emojis and keywords to stickers for instant sticker suggestions while typing messages.

### Authorization
Does not require authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `aliases` | integer | `1` — include text keyword aliases for emojis (e.g. "hi", "hello" for "👋"), `0` — exact emoji matches only. Default: `1`. |
| `all_products` | integer | `1` — return suggestions across all catalog packs, `0` — user active packs only. Default: `1`. |
| `need_stickers` | integer | `1` — include full sticker structs, `0` — sticker IDs only. Default: `1`. |
| `stickers_hash` | string | MD5 hash of stickers dictionary on client. |
| `products_hash` | string | MD5 hash of products list on client. |
| `count` | integer | Max records count. Default: `0` (all). |
| `user_id` | integer | User ID. |

### Result

Returns an object containing:
* `count` (integer) — number of suggestion groups in the dictionary;
* `dictionary` (array) — array of keyword-sticker association groups;
* `base_url` (string) — base URL for sticker images;
* `stickers_hash` (string) — stickers dictionary hash for caching;
* `products_hash` (string) — products list hash.

Each group in `dictionary` contains:
* `words` (array) — list of trigger words and emojis;
* `user_stickers` (array) — array of user stickers available for these triggers;
* `promoted_stickers` (array) — array of promoted stickers for purchase.

### Example Request
```http
POST /method/store.getStickersKeywords HTTP/1.1
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
                "words": [
                    "👋",
                    "hi",
                    "hello",
                    "hey"
                ],
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
