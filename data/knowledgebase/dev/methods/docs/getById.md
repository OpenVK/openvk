OpenVK-KB-Heading: docs.getById

# docs.getById

Returns information about documents by their identifiers.

Document identifiers are passed in format `owner_id_doc_id` or `owner_id_doc_id_access_key` (e.g. `1_10` or `1_10_4f8a1c9e2b3d4f5a6b`).

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `docs` | string | Comma-separated list of document identifiers (e.g. `1_1,1_2_abcdef1234`). **Required.** |
| `return_tags` | integer | `1` to include `tags` array for each document, `0` otherwise. Default: `0`. |

### Result

Returns an array of document objects (`[doc1, doc2, ...]`).

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `100` | `One of the parameters specified was missing or invalid: docs is undefined` |

### Request Example
```http
POST /method/docs.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

docs=1_1,1_2_4f8a1c9e2b3d4f5a6b&return_tags=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": [
        {
            "id": 1,
            "owner_id": 1,
            "true_owner_id": 1,
            "title": "Archive.zip",
            "size": 5242880,
            "ext": "zip",
            "url": "https://openvk.instance/blob_a1/hash.zip",
            "date": 1609459200,
            "type": 2,
            "is_hidden": 0,
            "is_licensed": 0,
            "is_unsafe": 0,
            "folder_id": 0,
            "access_key": "4f8a1c9e2b3d4f5a6b",
            "can_manage": true,
            "tags": [
                "sources"
            ]
        }
    ]
}
```
