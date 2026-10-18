OpenVK-KB-Heading: messages.getCounters

# messages.getCounters

Returns unread and unanswered message counters across messenger sections and folders.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `filter` | integer | Filter flag. Default: `0`. |

### Result

Returns an object containing:
* `messages` (integer) — Total unread messages count;
* `messages_unread_unmuted` (integer) — Unread messages count in non-muted chats;
* `message_requests` (integer) — Pending message requests count;
* `important` (integer) — Unread important messages count;
* `unanswered` (integer) — Unanswered dialogues count;
* `messages_folders` (array) — Unread statistics per folder.

### Example Request
```http
POST /method/messages.getCounters HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```
