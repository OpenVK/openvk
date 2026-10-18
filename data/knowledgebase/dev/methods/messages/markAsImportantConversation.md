OpenVK-KB-Heading: messages.markAsImportantConversation

# messages.markAsImportantConversation

Marks or unmarks an entire conversation as important.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination ID (`2000000000 + chat_id` or user ID). **Required.** |
| `important` | integer | `1` — mark as important, `0` — unmark. Default: `1`. |
| `group_id` | integer | Community identifier. |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.markAsImportantConversation HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&important=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
