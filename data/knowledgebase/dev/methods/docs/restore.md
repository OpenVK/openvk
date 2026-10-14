OpenVK-KB-Heading: docs.restore

# docs.restore

Restores a previously deleted document into the current user's collection.

### Authorization
Requires user authorization (`access_token`) with the `docs` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Identifier of the document owner. **Required.** |
| `doc_id` | integer | Document identifier. **Required.** |

### Result

Returns the restored document identifier in format `virtual_id_doc_id`.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `1150` | `Invalid document id` |

### Request Example
```http
POST /method/docs.restore HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&doc_id=45&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": "1_45"
}
```
