OpenVK-KB-Heading: messages.markAsRead

# messages.markAsRead

Marks messages in a dialogue or conversation as read.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `message_ids` | string | Comma-separated list of message IDs to mark as read. |
| `peer_id` | integer | Destination ID (marks all messages up to `start_message_id` as read). |
| `start_message_id` | integer | ID of the last message to mark as read. |
| `group_id` | integer | Community identifier. |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.markAsRead HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&start_message_id=4512&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
