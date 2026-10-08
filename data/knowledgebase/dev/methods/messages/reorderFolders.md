OpenVK-KB-Heading: messages.reorderFolders

# messages.reorderFolders

Reorders the custom chat folders list of the current user.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `folder_ids` | string | Comma-separated list of folder IDs in new order. **Required.** |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.reorderFolders HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

folder_ids=2,1,3&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
