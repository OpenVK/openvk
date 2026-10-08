OpenVK-KB-Heading: messages.getConversations

# messages.getConversations

Returns a list of conversations (dialogues and chats) of the current user in modern API format (v >= 5.80).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `offset` | integer | Offset needed to return a specific subset of conversations. Default: `0`. |
| `count` | integer | Number of conversations to return (max `200`). Default: `20`. |
| `filter` | string | Filter: `"all"` — all conversations, `"unread"` — unread only, `"important"` — marked as important, `"unanswered"` — unanswered conversations. Default: `"all"`. |
| `extended` | integer | `1` — return user and community profile objects. Default: `0`. |
| `fields` | string | Comma-separated list of additional profile fields. Default: `"photo_200,online"`. |
| `group_id` | integer | Community identifier (if acting on behalf of a group). |

### Result

Returns an object containing:
* `count` (integer) — Total count of conversations matching the filter;
* `unread_count` (integer, optional) — Total unread conversations count;
* `items` (array) — Array of objects, each containing:
  * `conversation` (object) — **[Conversation](/dev/models/conversation)** object;
  * `last_message` (object) — Last **[Message](/dev/models/message)** in the conversation;
* `profiles` (array, optional) — User profile objects (when `extended=1`);
* `groups` (array, optional) — Community profile objects (when `extended=1`).

### Example Request
```http
POST /method/messages.getConversations HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

filter=all&count=20&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 1,
        "unread_count": 0,
        "items": [
            {
                "conversation": {
                    "peer": {
                        "id": 2000000001,
                        "type": "chat",
                        "local_id": 1
                    },
                    "in_read": 4512,
                    "out_read": 4512,
                    "unread_count": 0,
                    "important": false,
                    "chat_settings": {
                        "title": "OpenVK Developers",
                        "members_count": 3,
                        "state": "in"
                    }
                },
                "last_message": {
                    "id": 4512,
                    "conversation_message_id": 42,
                    "date": 1696680000,
                    "peer_id": 2000000001,
                    "from_id": 1,
                    "text": "Hello developers!",
                    "out": 1,
                    "attachments": []
                }
            }
        ]
    }
}
```
