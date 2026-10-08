OpenVK-KB-Heading: messages.getHistory

# messages.getHistory

Returns message history for a specified dialogue or multi-user chat.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination identifier (user `id`, group `-id`, or `2000000000 + chat_id`). |
| `user_id` | integer | User identifier (for 1-on-1 dialogue). |
| `chat_id` | integer | Multi-user chat identifier (`1...N`). |
| `offset` | integer | Offset needed to return a specific subset of messages. Default: `0`. |
| `count` | integer | Number of messages to return (max `200`). Default: `20`. |
| `start_message_id` | integer | Starting message ID for retrieving history. |
| `rev` | integer | Sort order: `1` — chronological (oldest first), `0` — reverse chronological (newest first). Default: `0`. |
| `extended` | integer | `1` — return user and community profile objects. Default: `0`. |
| `preview_length` | integer | Maximum characters for message text preview (`0` for full text). |
| `fields` | string | Profile fields to return when `extended=1`. Default: `"photo_200,online"`. |

---

### Result

#### API version 5.80 and higher (v >= 5.80)
Returns an object containing:
* `count` (integer) — Total count of messages in the dialogue;
* `items` (array) — Array of modern **[Message](/dev/models/message)** objects;
* `conversations` (array, optional) — Array of **[Conversation](/dev/models/conversation)** objects;
* `profiles` (array, optional) — User profiles (when `extended=1`);
* `groups` (array, optional) — Community profiles (when `extended=1`).

```json
{
    "response": {
        "count": 100,
        "items": [
            {
                "id": 4512,
                "conversation_message_id": 42,
                "date": 1696680000,
                "peer_id": 2000000001,
                "from_id": 1,
                "text": "Hello developers!",
                "out": 1,
                "attachments": [],
                "fwd_messages": []
            }
        ]
    }
}
```

#### API versions 5.0 — 5.79 (5.0 <= v < 5.80)
Returns an object containing:
* `count` (integer) — Total count of messages;
* `items` (array) — Array of classic message objects with `body`, `read_state`, `user_id`, etc.

#### Legacy API versions (v < 5.0)
Returns an array where the first element is the total count of messages, followed by legacy message objects with `mid` and `uid`:
```json
{
    "response": [
        100,
        {
            "mid": 4512,
            "date": 1696680000,
            "out": 1,
            "uid": 1,
            "read_state": 1,
            "body": "Hello developers!",
            "attachments": []
        }
    ]
}
```

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `100` | `One of the parameters specified was missing or invalid: peer_id, user_id or chat_id required` |
| `15` | `Access denied` |

### Example Request
```http
POST /method/messages.getHistory HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&count=20&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
