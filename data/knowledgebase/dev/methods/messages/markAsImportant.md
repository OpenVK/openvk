OpenVK-KB-Heading: messages.markAsImportant

# messages.markAsImportant

Marks or unmarks messages as important (starred).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `message_ids` | string | Comma-separated list of message IDs. **Required.** |
| `important` | integer | `1` — mark as important, `0` — unmark. Default: `1`. |

### Result

Returns an array of message IDs whose status was updated.

### Example Request
```http
POST /method/messages.markAsImportant HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

message_ids=4512&important=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
