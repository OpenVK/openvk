OpenVK-KB-Heading: messages.send

# messages.send

Sends a message to a user, a community, or a multi-user chat. Supports text, attachments, stickers, reply/forward quotes, and geo-coordinates.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination ID: user ID, community `-id`, or `2000000000 + chat_id` for chat. |
| `user_id` | integer | User identifier (if sending a 1-on-1 private message). |
| `chat_id` | integer | Chat ID (number `1...N` without the 2000000000 offset). |
| `domain` | string | User or community short name/screen_name. |
| `message` | string | Message text content. |
| `attachment` | string | Comma-separated list of attachments in `<type><owner_id>_<media_id>` format (e.g., `photo1_456239017,doc1_123`). |
| `sticker_id` | integer | Sticker ID to send. |
| `random_id` | integer | Unique client-side integer ID (or `guid`) used to prevent duplicate messages on connection retry. |
| `reply_to` | integer | ID of the message being replied to (quote). |
| `forward_messages` | string | Comma-separated list of message IDs to forward. |
| `forward` | string | JSON object with forward payload (e.g. `{"conversation_message_ids": [1, 2], "is_reply": true}`). |
| `lat` | float | Geographical latitude. |
| `long` | float | Geographical longitude. |
| `unnoticed` | integer | `1` — do not update sender's online status, `0` — update. Default: `0`. |

> **Note:** At least one destination identifier (`peer_id`, `user_id`, `chat_id`, or `domain`) and at least one content item (`message`, `attachment`, `sticker_id`, or `forward_messages`) must be provided.

### Result

Returns the ID of the sent message (integer).

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `100` | `One of the parameters specified was missing or invalid: receiver not specified` |
| `15` | `Access denied: cannot send messages to this user` (privacy restrictions or blacklist). |
| `900` | `Can't send messages for users from blacklist` |
| `902` | `Can't send messages to this user due to privacy settings` |

### Example Request
```http
POST /method/messages.send HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&message=Hello+World!&random_id=18492048&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 4512
}
```
