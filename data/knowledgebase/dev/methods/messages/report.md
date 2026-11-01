OpenVK-KB-Heading: messages.report

# messages.report

Reports a message as spam or policy violation.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination ID where message is located. **Required.** |
| `message_id` | integer | ID of the reported message. **Required.** |
| `type` | string | Report reason type (e.g. `"spam"`). Default: `"spam"`. |
| `comment` | string | Optional description or comment. |

### Result

Returns `1` on success.

### Example Request
```http
POST /method/messages.report HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&message_id=4512&type=spam&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
