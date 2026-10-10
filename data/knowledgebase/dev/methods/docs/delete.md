OpenVK-KB-Heading: docs.delete

# docs.delete

Deletes a document from the user or community collection.

### Authorization
Requires user authorization (`access_token`) with the `docs` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Identifier of the document owner. **Required.** |
| `doc_id` | integer | Document identifier. **Required.** |

### Result

Returns `1` on successful deletion.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `1150` | `Invalid document id` |
| `1153` | `Access to document is denied` |

### Request Example
```http
POST /method/docs.delete HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&doc_id=45&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
