OpenVK-KB-Heading: messages.deleteChatPhoto

# messages.deleteChatPhoto

Deletes the cover photo of a multi-user chat.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `chat_id` | integer | Chat ID (`1...N`). **Required.** |

### Result

Returns an object containing:
* `message_id` (integer) — Service message ID created in chat;
* `chat` (object) — Updated **[Chat](/dev/models/chat)** object without photos.

### Example Request
```http
POST /method/messages.deleteChatPhoto HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

chat_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
