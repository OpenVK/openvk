OpenVK-KB-Heading: messages.allowMessagesFromGroup

# messages.allowMessagesFromGroup

Allows the specified community to send messages to the current user.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | Community identifier (positive integer). **Required.** |
| `key` | string | Optional verification key. |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.allowMessagesFromGroup HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
