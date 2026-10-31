OpenVK-KB-Heading: messages.getDialogs

# messages.getDialogs

Legacy method for returning a list of dialogues of the current user.

> **Important:** In API version 5.80 and higher, use **[messages.getConversations](/dev/methods/messages/getConversations)**.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `offset` | integer | Offset needed to return a specific subset of dialogues. Default: `0`. |
| `count` | integer | Number of dialogues to return (max `200`). Default: `20`. |
| `unread` | integer | `1` — return unread dialogues only, `0` — all. Default: `0`. |
| `preview_length` | integer | Character limit for preview text (`0` for full text). |
| `extended` | integer | `1` — return user and community profile objects. Default: `0`. |
| `fields` | string | Profile fields when `extended=1`. |

---

### Result

#### API version 5.0 — 5.79 (5.0 <= v < 5.80)
Returns an object containing:
* `count` (integer) — Total count of dialogues;
* `items` (array) — Array of last message objects for each dialogue (including `chat_id`, `chat_active`, `users_count`, `admin_id` for chats);
* `profiles` (array, optional) — Profiles;
* `groups` (array, optional) — Communities.

```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 4512,
                "date": 1696680000,
                "out": 1,
                "user_id": 1,
                "read_state": 1,
                "title": "OpenVK Developers",
                "body": "Hello developers!",
                "chat_id": 1,
                "chat_active": [1, 2, 3],
                "users_count": 3,
                "admin_id": 1,
                "attachments": []
            }
        ]
    }
}
```

#### Legacy API versions (v < 5.0)
Returns an array where the first element is the total count, followed by message objects:
```json
{
    "response": [
        1,
        {
            "mid": 4512,
            "date": 1696680000,
            "out": 1,
            "uid": 1,
            "read_state": 1,
            "title": "OpenVK Developers",
            "body": "Hello developers!",
            "chat_id": 1,
            "attachments": []
        }
    ]
}
```

### Example Request
```http
POST /method/messages.getDialogs HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=20&access_token=YOUR_ACCESS_TOKEN&v=5.78
```
