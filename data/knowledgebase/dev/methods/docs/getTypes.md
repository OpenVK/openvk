OpenVK-KB-Heading: docs.getTypes

# docs.getTypes

Returns document type categories and total file counts per category for the specified owner.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Identifier of the documents owner (positive for current user, negative for community). Default: current user ID. |

### Result

Returns an object containing:
* `count` (integer) — number of available categories;
* `items` (array) — array of category objects, each containing:
  * `type` (integer) — numeric type identifier (`1` — text, `2` — archive, `3` — GIF, `4` — image, `5` — audio, `6` — video, `7` — book, `8` — other);
  * `name` (string) — localized category name;
  * `count` (integer) — number of files in this category.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `15` | `Access denied` |

### Request Example
```http
POST /method/docs.getTypes HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 3,
        "items": [
            {
                "type": 1,
                "name": "Text documents",
                "count": 12
            },
            {
                "type": 2,
                "name": "Archives",
                "count": 4
            },
            {
                "type": 7,
                "name": "E-books",
                "count": 2
            }
        ]
    }
}
```
