OpenVK-KB-Heading: messages.setActivity

# messages.setActivity

Sends status indicating that the current user is typing text or recording an audio message in a dialogue or chat.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination ID (user ID or `2000000000 + chat_id`). |
| `user_id` | integer | User identifier (for private 1-on-1 dialogue). |
| `type` | string | Activity type: `"typing"` (typing text) or `"audiomessage"` (recording voice). Default: `"typing"`. |
| `group_id` | integer | Community identifier. |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.setActivity HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&type=typing&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
