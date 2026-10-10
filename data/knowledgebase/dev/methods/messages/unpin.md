OpenVK-KB-Heading: messages.unpin

# messages.unpin

Unpins the pinned message in a conversation or multi-user chat.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination ID (`2000000000 + chat_id` or user ID). **Required.** |
| `group_id` | integer | Community identifier. |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.unpin HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
