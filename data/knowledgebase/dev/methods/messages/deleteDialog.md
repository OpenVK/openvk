OpenVK-KB-Heading: messages.deleteDialog

# messages.deleteDialog

Deletes all messages in a dialogue or chat (legacy alias for `messages.deleteConversation`).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | User identifier (for 1-on-1 dialogue). |
| `peer_id` | integer | Destination ID. |
| `chat_id` | integer | Chat ID. |
| `offset` | integer | Offset. |
| `count` | integer | Count. |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.deleteDialog HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
