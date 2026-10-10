OpenVK-KB-Heading: messages.getChat

# messages.getChat

Returns information about a multi-user chat (title, administrator, active members, cover photos).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `chat_id` | integer | Chat ID (number `1...N`). |
| `chat_ids` | string | Comma-separated list of chat IDs. |
| `fields` | string | Profile fields of chat members. |
| `name_case` | string | Grammatical case for user names. |

---

### Result

#### API version 5.0 and higher (v >= 5.0)
Returns a **[Chat](/dev/models/chat)** object (or array of chat objects if `chat_ids` is used):

```json
{
    "response": {
        "type": "chat",
        "id": 1,
        "title": "OpenVK Developers",
        "admin_id": 1,
        "users": [1, 2, 3],
        "members_count": 3,
        "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
        "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
        "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
    }
}
```

#### Legacy API versions (v < 5.0)
Returns an object with `chat_id` and `users`:
```json
{
    "response": {
        "chat_id": 1,
        "type": "chat",
        "title": "OpenVK Developers",
        "admin_id": 1,
        "users": [1, 2, 3]
    }
}
```

### Example Request
```http
POST /method/messages.getChat HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

chat_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
