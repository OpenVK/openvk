OpenVK-KB-Heading: docs.get

# docs.get

Returns a list of documents of the currently authorized user or community.

### Authorization
Requires user authorization (`access_token`) with the `docs` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `count` | integer | Number of documents to return. Default: `30`. |
| `offset` | integer | Offset needed to return a specific subset of documents. Default: `0`. |
| `type` | integer | Filter by document type (`1` — text, `2` — archive, `3` — GIF, `4` — image, `5` — audio, `6` — video, `7` — book, `8` — other, `-1` — all types). Default: `-1`. |
| `owner_id` | integer | Identifier of the documents owner (positive number for current user, negative for community). Access to other users' documents is restricted. Default: current user ID. |
| `return_tags` | integer | `1` to return a `tags` array for each document, `0` otherwise. Default: `0`. |
| `order` | integer | Sort order: `0` (by date added, newest first), `1` (by name ascending), `2` (by file size descending). Default: `0`. |

### Result

For API versions 5.0 and higher, returns an object containing:
* `count` (integer) — total number of documents;
* `items` (array) — array of document objects.

For API versions prior to 5.0, returns an array formatted as `[count, doc1, doc2, ...]`.

Each document object contains:
| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Document identifier. |
| `owner_id` | integer | Owner identifier (virtual ID). |
| `true_owner_id` | integer | Real owner ID (or `0` if owner is hidden). |
| `title` | string | Document title. |
| `size` | integer | File size in bytes. |
| `ext` | string | File extension (e.g., `pdf`, `zip`, `docx`). |
| `url` | string | Direct download URL. |
| `date` | integer | Creation time (Unix timestamp). |
| `type` | integer | Numeric document type (1..8). |
| `is_hidden` | integer | `1` if owner is hidden, `0` otherwise. |
| `is_licensed` | integer | License flag. |
| `is_unsafe` | integer | Unsafe flag. |
| `folder_id` | integer | Folder ID (`0` — private, `3` — public). |
| `access_key` | string | Document access key. |
| `can_manage` | boolean | Whether current user has management permissions. |
| `preview` | object | Preview image sizes object (for GIFs and images). |
| `tags` | array | Array of string tags (when `return_tags=1`). |

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `15` | `Access denied` |

### Request Example
```http
POST /method/docs.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=2&return_tags=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "owner_id": 1,
                "true_owner_id": 1,
                "title": "Document.pdf",
                "size": 2048576,
                "ext": "pdf",
                "url": "https://openvk.instance/blob_a1/a1b2c3d4e5.pdf",
                "date": 1609459200,
                "type": 7,
                "is_hidden": 0,
                "is_licensed": 0,
                "is_unsafe": 0,
                "folder_id": 0,
                "access_key": "4f8a1c9e2b3d4f5a6b",
                "can_manage": true,
                "tags": [
                    "work",
                    "report"
                ]
            }
        ]
    }
}
```
