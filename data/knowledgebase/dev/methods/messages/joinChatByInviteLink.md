OpenVK-KB-Heading: messages.joinChatByInviteLink

# messages.joinChatByInviteLink

Joins a multi-user chat using an invite link.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `link` | string | Full invite link or invite hash. **Required.** |

### Result

Returns an object containing:
* `chat_id` (integer) — Chat ID (`1...N`).

### Example Request
```http
POST /method/messages.joinChatByInviteLink HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

link=https://openvk.instance/join/abc12345&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
