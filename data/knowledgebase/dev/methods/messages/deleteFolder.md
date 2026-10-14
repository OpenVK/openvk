OpenVK-KB-Heading: messages.deleteFolder

# messages.deleteFolder

Deletes a chat folder.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `folder_id` | integer | Folder identifier to delete. **Required.** |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.deleteFolder HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

folder_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
