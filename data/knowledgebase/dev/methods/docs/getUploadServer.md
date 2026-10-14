OpenVK-KB-Heading: docs.getUploadServer

# docs.getUploadServer

Returns the server upload URL for user or community documents.

> **Note:** In the current version of OpenVK, this method is a stub returning `0`.

### Authorization
Requires user authorization (`access_token`) with the `docs` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | Community identifier if uploading to a community. |

### Result

Returns `0` in current implementation.

### Request Example
```http
POST /method/docs.getUploadServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 0
}
```
