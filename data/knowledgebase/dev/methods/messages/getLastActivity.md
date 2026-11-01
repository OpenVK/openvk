OpenVK-KB-Heading: messages.getLastActivity

# messages.getLastActivity

Returns information about user's online presence and last activity timestamp.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | Identifier of the user. **Required.** |

### Result

Returns an object containing:
* `online` (integer) — `1` if user is currently online, `0` otherwise;
* `time` (integer) — Unix timestamp of user's last activity.

### Example Request
```http
POST /method/messages.getLastActivity HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "online": 1,
        "time": 1696680000
    }
}
```
