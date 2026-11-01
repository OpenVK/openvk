OpenVK-KB-Heading: docs.add

# docs.add

Copies a document to the currently authorized user's documents.

### Authorization
Requires user authorization (`access_token`) with the `docs` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Identifier of the document owner. **Required.** |
| `doc_id` | integer | Document identifier. **Required.** |
| `access_key` | string | Access key for private documents. |

### Result

Returns a string identifier of the created document copy in format `virtual_id_doc_id` (e.g. `"1_45"`).

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `15` | `Access denied` — Invalid access key. |
| `100` | `this document already added` — Document is already present in the user's collection. |
| `1150` | `Invalid document id` |

### Request Example
```http
POST /method/docs.add HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=2&doc_id=10&access_key=4f8a1c9e2b3d4f5a6b&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": "1_45"
}
```
