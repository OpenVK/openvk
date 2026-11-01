OpenVK-KB-Heading: messages.getByConversationMessageId

# messages.getByConversationMessageId

Returns messages by their local conversation message IDs (`conversation_message_ids`) inside a specific conversation/chat.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination identifier (user `id`, community `-id`, or `2000000000 + chat_id`). **Required.** |
| `conversation_message_ids` | string | Comma-separated list of local conversation message IDs. **Required.** |
| `extended` | integer | `1` — return user and community profile objects. Default: `0`. |
| `fields` | string | Comma-separated profile fields when `extended=1`. Default: `"photo_200,online"`. |
| `group_id` | integer | Community ID (if acting on behalf of a community). |

### Result

Returns an object containing:
* `count` (integer) — Number of returned messages;
* `items` (array) — Array of modern **[Message](/dev/models/message)** objects;
* `profiles` (array, optional) — Profiles of message authors (when `extended=1`);
* `groups` (array, optional) — Communities of message authors (when `extended=1`).

```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 4512,
                "conversation_message_id": 42,
                "date": 1696680000,
                "peer_id": 2000000001,
                "from_id": 1,
                "text": "Hello!",
                "out": 1,
                "attachments": [],
                "fwd_messages": []
            }
        ]
    }
}
```

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `100` | `One of the parameters specified was missing or invalid: peer_id and conversation_message_ids required` |
| `15` | `Access denied: cannot view conversation messages` |

### Example Request
```http
POST /method/messages.getByConversationMessageId HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&conversation_message_ids=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
