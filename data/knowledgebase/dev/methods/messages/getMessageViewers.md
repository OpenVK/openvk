OpenVK-KB-Heading: messages.getMessageViewers

# messages.getMessageViewers

Returns the list of participants who have viewed/read a specific message in a multi-user chat.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination ID (`2000000000 + chat_id`). **Required.** |
| `conversation_message_id` | integer | Local conversation message ID. |
| `message_id` | integer | Global message ID. |
| `extended` | integer | `1` — return user profile objects. Default: `0`. |
| `fields` | string | Profile fields to return when `extended=1`. |

### Result

Returns an object containing:
* `user_ids` (array) — Array of user IDs who viewed the message;
* `profiles` (array, optional) — Array of user profile objects (when `extended=1`).

### Example Request
```http
POST /method/messages.getMessageViewers HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&conversation_message_id=42&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
