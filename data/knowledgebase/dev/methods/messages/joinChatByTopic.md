OpenVK-KB-Heading: messages.joinChatByTopic

# messages.joinChatByTopic

Joins a multi-user chat linked to a community discussion board topic.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | Community ID (positive integer). **Required.** |
| `topic_id` | integer | Discussion topic ID. **Required.** |

### Result

Returns an object containing:
* `chat_id` (integer) — Linked chat ID (`1...N`).

### Example Request
```http
POST /method/messages.joinChatByTopic HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&topic_id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
