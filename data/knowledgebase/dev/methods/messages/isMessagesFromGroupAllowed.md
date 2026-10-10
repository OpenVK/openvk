OpenVK-KB-Heading: messages.isMessagesFromGroupAllowed

# messages.isMessagesFromGroupAllowed

Checks whether a specified user is allowed to receive messages from the community.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | Community identifier. **Required.** |
| `user_id` | integer | Target user identifier. Default: current user ID. |

### Result

Returns an object containing:
* `is_allowed` (integer) — `1` if allowed, `0` if denied.

### Example Request
```http
POST /method/messages.isMessagesFromGroupAllowed HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&user_id=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "is_allowed": 1
    }
}
```
