OpenVK-KB-Heading: messages.get

# messages.get

Returns a list of incoming or outgoing messages for the current user.

> **Note:** In modern clients (API 5.80+), it is recommended to use **[messages.getConversations](/dev/methods/messages/getConversations)** or **[messages.getHistory](/dev/methods/messages/getHistory)**.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `out` | integer | `1` — return outgoing messages, `0` — return incoming messages. Default: `0`. |
| `offset` | integer | Offset needed to return a specific subset of messages. Default: `0`. |
| `count` | integer | Number of messages to return (max `200`). Default: `20`. |
| `time_offset` | integer | Maximum time offset (in seconds) from the current time. Default: `0`. |
| `filters` | integer | Filter flags bitmask: `1` — unread, `2` — not from chat, `4` — from friends. |
| `preview_length` | integer | Number of characters to truncate message text to (`0` for full text). |
| `last_message_id` | integer | ID of the message starting from which messages should be returned. |
| `extended` | integer | `1` — return user and group profile objects. Default: `0`. |
| `fields` | string | Additional profile fields to return when `extended=1`. |

---

### Result

#### API version 5.0 and higher (v >= 5.0)
Returns an object containing:
* `count` (integer) — Total number of messages matching the criteria;
* `items` (array) — Array of **[Message](/dev/models/message)** objects;
* `profiles` (array, optional) — User profile objects (when `extended=1`);
* `groups` (array, optional) — Community objects (when `extended=1`).

```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 4512,
                "date": 1696680000,
                "out": 0,
                "user_id": 1,
                "from_id": 1,
                "read_state": 1,
                "title": "Welcome",
                "body": "Hello!",
                "attachments": [],
                "fwd_messages": []
            }
        ]
    }
}
```

#### Legacy API versions (v < 5.0)
Returns an array where the first element is the total count of messages, followed by legacy message objects with `mid` and `uid`:
```json
{
    "response": [
        1,
        {
            "mid": 4512,
            "date": 1696680000,
            "out": 0,
            "uid": 1,
            "read_state": 1,
            "title": "Welcome",
            "body": "Hello!",
            "attachments": []
        }
    ]
}
```

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |

### Example Request
```http
POST /method/messages.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

out=0&count=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
