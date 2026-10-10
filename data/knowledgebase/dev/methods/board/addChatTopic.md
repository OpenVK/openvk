OpenVK-KB-Heading: board.addChatTopic

# board.addChatTopic

Creates a discussion topic linked to a group chat (`type = "chat"`). If `chat_id` is omitted, creates a new chat with the given `title`. If an existing `chat_id` is passed, links the topic to that chat.

> **Note:** This method is only available to community administrators. To link an existing chat, the user must also be an administrator of that chat, and the chat must not already be linked to another topic.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | Community identifier. **Required.** |
| `title` | string | Chat title (used when creating a new chat). **Required.** |
| `chat_id` | string | Existing chat ID (e.g., `12` or peer ID `2000000012`) to link with the topic. |

### Result

Returns the integer identifier of the created topic inside the community (`topic_id`).

### Error Codes

| Code | Description |
| --- | --- |
| `-5` | `Invalid chat` |
| `5` | `User authorization failed: no access_token passed.` |
| `14` | `Chat already linked to some topic` |
| `15` | `Access denied` |

### Request Example
```http
POST /method/board.addChatTopic HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&title=Official+Community+Chat&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 7
}
```
