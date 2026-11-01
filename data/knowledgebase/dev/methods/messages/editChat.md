OpenVK-KB-Heading: messages.editChat

# messages.editChat

Edits the title of a multi-user chat.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `chat_id` | integer | Chat ID (`1...N`). **Required.** |
| `title` | string | New chat title. **Required.** |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.editChat HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

chat_id=1&title=OpenVK+Core+Team&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
