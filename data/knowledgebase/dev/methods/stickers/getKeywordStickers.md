OpenVK-KB-Heading: stickers.getKeywordStickers

# stickers.getKeywordStickers

Returns a list of stickers matching specific keywords.

> **Note:** In OpenVK, this method is a compatibility stub and returns an empty array. To get sticker suggestions, use [stickers.getStickersKeywords](/dev/methods/stickers/getStickersKeywords).

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `words` | string | Comma-separated search words. |
| `need_stickers` | integer | `1` — return sticker objects, `0` — do not return. Default: `1`. |
| `aliases` | string | Keyword aliases. |

### Result

Returns an empty array `[]`.

### Example Request
```http
POST /method/stickers.getKeywordStickers HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

words=hello&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": []
}
```
