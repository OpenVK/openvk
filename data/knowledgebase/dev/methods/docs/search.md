OpenVK-KB-Heading: docs.search

# docs.search

Searches public documents across the platform by filename and tags.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `q` | string | Search query by document filename (up to 512 characters). Default: `""`. |
| `search_own` | integer | `1` to search only among the current user's documents, `-1` or `0` across all public documents. Default: `-1`. |
| `type` | integer | Filter by document type (`1` — text, `2` — archive, `3` — GIF, `4` — image, `5` — audio, `6` — video, `7` — book, `8` — other, `0` — all types). Default: `0`. |
| `tags` | string | Filter by tags (up to 512 characters). |
| `offset` | integer | Offset needed to return a specific subset of results. Default: `0`. |
| `count` | integer | Number of documents to return. Default: `30`. |
| `return_tags` | integer | `1` to include `tags` array for each document. Default: `0`. |
| `order` | integer | Sort order. Default: `-1`. |

### Result

Returns an object containing:
* `count` (integer) — number of matching documents;
* `items` (array) — array of document objects.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `100` | `One of the parameters specified was missing or invalid: q should be not more 512 letters length` |

### Request Example
```http
POST /method/docs.search HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=report&type=1&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 12,
                "owner_id": 1,
                "true_owner_id": 1,
                "title": "financial_report.xlsx",
                "size": 1048576,
                "ext": "xlsx",
                "url": "https://openvk.instance/blob_b2/hash.xlsx",
                "date": 1609459200,
                "type": 1,
                "is_hidden": 0,
                "is_licensed": 0,
                "is_unsafe": 0,
                "folder_id": 3,
                "access_key": "4f8a1c9e2b3d4f5a6b",
                "can_manage": true
            }
        ]
    }
}
```
