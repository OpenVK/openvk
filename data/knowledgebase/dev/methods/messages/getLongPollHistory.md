OpenVK-KB-Heading: messages.getLongPollHistory

# messages.getLongPollHistory

Returns events and message updates from LongPoll history starting from the given timestamp (`ts`) or sequence number (`pts`).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `ts` | integer | Starting timestamp / event ID. |
| `pts` | integer | Starting sequence number. |
| `preview_length` | integer | Character limit for preview text. |
| `extended` | integer | `1` — return user and community profile objects. |
| `fields` | string | Profile fields. |
| `events_limit` | integer | Max events to return (max `1000`). Default: `1000`. |
| `msgs_limit` | integer | Max messages to return (max `1000`). Default: `200`. |
| `max_msg_id` | integer | Max message ID to return. |
| `group_id` | integer | Community identifier. |

### Result

Returns an object containing:
* `history` (array) — Array of event arrays (e.g. `[4, message_id, flags, peer_id, timestamp, text, attachments]`);
* `messages` (object) — Object with `count` and `items` (array of **[Message](/dev/models/message)** objects);
* `profiles` (array, optional) — User profiles;
* `groups` (array, optional) — Community profiles;
* `new_pts` (integer) — New `pts` value to use in subsequent requests;
* `more` (boolean) — `true` if more events are available.

### Example Request
```http
POST /method/messages.getLongPollHistory HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

ts=1696680000&pts=450&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
