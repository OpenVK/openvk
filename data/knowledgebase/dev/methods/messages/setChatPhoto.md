OpenVK-KB-Heading: messages.setChatPhoto

# messages.setChatPhoto

Sets the cover photo for a multi-user chat using the upload response string.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `file` | string | String returned by the photo upload server. **Required.** |

### Result

Returns an object containing:
* `message_id` (integer) — Service message ID created in chat;
* `chat` (object) — Updated **[Chat](/dev/models/chat)** object.

### Example Request
```http
POST /method/messages.setChatPhoto HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

file=eyJhbGciOiJIUzI1NiJ9...&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
