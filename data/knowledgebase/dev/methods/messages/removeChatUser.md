OpenVK-KB-Heading: messages.removeChatUser

# messages.removeChatUser

Removes a user from a multi-user chat or allows the current user to leave the chat.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `chat_id` | integer | Chat ID (`1...N`). |
| `peer_id` | integer | Destination ID (`2000000000 + chat_id`). |
| `user_id` | integer | ID of the user to remove (if removing another user) or current user ID (to leave chat). **Required.** |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.removeChatUser HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

chat_id=1&user_id=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
