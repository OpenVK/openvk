OpenVK-KB-Heading: messages.delete

# messages.delete

Deletes messages for the current user or for all participants in a dialogue/chat.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `message_ids` | string | Comma-separated list of global message IDs. |
| `peer_id` | integer | Destination ID (when using `conversation_message_ids`). |
| `conversation_message_ids` | string | Comma-separated list of local conversation message IDs. |
| `delete_for_all` | integer | `1` — delete message for all dialogue/chat participants, `0` — delete only for current user. Default: `0`. |
| `spam` | integer | `1` — mark message as spam. Default: `0`. |

### Result

Returns a key-value object mapping each message ID to deletion status (`1` on success, `0` on failure):
```json
{
    "response": {
        "4512": 1
    }
}
```

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `100` | `One of the parameters specified was missing or invalid: message_ids required` |
| `924` | `Can't delete message for everyone: time limit expired or no admin privileges` |

### Example Request
```http
POST /method/messages.delete HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

message_ids=4512&delete_for_all=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "4512": 1
    }
}
```
