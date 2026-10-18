OpenVK-KB-Heading: messages.restore

# messages.restore

Restores a previously deleted message.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `message_id` | integer | Global ID of the message to restore. **Required.** |
| `group_id` | integer | Community identifier (if acting on behalf of a group). |

### Result

Returns `1` on success.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `100` | `One of the parameters specified was missing or invalid: message_id is required` |
| `910` | `Can't restore message: message has been permanently deleted` |

### Example Request
```http
POST /method/messages.restore HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

message_id=4512&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
