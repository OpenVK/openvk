OpenVK-KB-Heading: messages.markAsAnsweredConversation

# messages.markAsAnsweredConversation

Marks or unmarks a conversation as answered.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination ID (`2000000000 + chat_id` or user ID). **Required.** |
| `answered` | integer | `1` — mark as answered, `0` — mark as unanswered. Default: `1`. |
| `group_id` | integer | Community identifier. |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.markAsAnsweredConversation HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&answered=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
