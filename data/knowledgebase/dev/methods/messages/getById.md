OpenVK-KB-Heading: messages.getById

# messages.getById

Returns message objects by their global message IDs.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `message_ids` | string | Comma-separated list of message IDs (e.g. `4512,4513`). **Required.** |
| `preview_length` | integer | Maximum character length for preview. Default: `0` (full text). |
| `extended` | integer | `1` — return user and group profile objects. Default: `0`. |
| `fields` | string | Additional profile fields when `extended=1`. Default: `"photo_200,online"`. |

### Result

#### API version 5.0 and higher (v >= 5.0)
Returns an object containing:
* `count` (integer) — Total count of returned messages;
* `items` (array) — Array of **[Message](/dev/models/message)** objects;
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
                "text": "Hello World!",
                "out": 1,
                "attachments": [],
                "fwd_messages": []
            }
        ]
    }
}
```

#### Legacy API versions (v < 5.0)
Returns an array `[count, message1, ...]`.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `100` | `One of the parameters specified was missing or invalid: message_ids required` |

### Example Request
```http
POST /method/messages.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

message_ids=4512&extended=0&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
