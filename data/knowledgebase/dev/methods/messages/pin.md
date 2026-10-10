OpenVK-KB-Heading: messages.pin

# messages.pin

Pins a message in a conversation or multi-user chat.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination ID (`2000000000 + chat_id` or user ID). **Required.** |
| `message_id` | integer | Global ID of the message to pin. |
| `conversation_message_id` | integer | Local conversation message ID to pin. |

### Result

Returns the pinned **[Message](/dev/models/message)** object.

### Example Request
```http
POST /method/messages.pin HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&conversation_message_id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
