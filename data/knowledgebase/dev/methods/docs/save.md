OpenVK-KB-Heading: docs.save

# docs.save

Saves a document after uploading it to the server.

> **Note:** In the current version of OpenVK, this method is a stub returning `0`.

### Authorization
Requires user authorization (`access_token`) with the `docs` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `file` | string | Upload server response data. **Required.** |
| `title` | string | Document title. **Required.** |
| `tags` | string | Comma-separated tags. **Required.** |
| `return_tags` | integer | `1` to include `tags` array in the returned document object. Default: `0`. |

### Result

Returns `0` in current implementation.

### Request Example
```http
POST /method/docs.save HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

file=UPLOAD_RESPONSE&title=Report.docx&tags=report&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 0
}
```
