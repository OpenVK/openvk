OpenVK-KB-Heading: messages.edit

# messages.edit

Edits the text or attachments of a previously sent message.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination ID where the message is located. |
| `message_id` | integer | Global ID of the message to edit. |
| `conversation_message_id` | integer | Local conversation message ID (when using `peer_id`). |
| `message` | string | New message text content. |
| `attachment` | string | New comma-separated list of attachments in `<type><owner_id>_<media_id>` format. |
| `keep_forward_messages` | integer | `1` to keep forwarded messages attached, `0` to remove them. |
| `keep_snippets` | integer | `1` to keep link snippets attached. |

### Result

Returns `1` on success.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `100` | `One of the parameters specified was missing or invalid: message not found` |
| `909` | `Can't edit message: message edit timeout expired or message was sent by another user` |

### Example Request
```http
POST /method/messages.edit HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&message_id=4512&message=Updated+message+text&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
