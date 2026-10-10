OpenVK-KB-Heading: messages.deleteConversation

# messages.deleteConversation

Deletes all messages in a conversation for the current user.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination ID (user `id`, group `-id`, or `2000000000 + chat_id`). |
| `user_id` | integer | User identifier (for 1-on-1 dialogue). |
| `group_id` | integer | Community identifier. |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.deleteConversation HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
