OpenVK-KB-Heading: messages.createChat

# messages.createChat

Creates a new multi-user chat (conversation) with the specified participants.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_ids` | string | Comma-separated list of user IDs to add to the chat. **Required.** |
| `title` | string | Chat title. **Required.** |
| `group_id` | integer | Community ID (if acting on behalf of a group). |

### Result

Returns the created chat ID (integer, number `1...N`).

### Example Request
```http
POST /method/messages.createChat HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_ids=2,3&title=OpenVK+Developers&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
