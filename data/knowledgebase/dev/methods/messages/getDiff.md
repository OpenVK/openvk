OpenVK-KB-Heading: messages.getDiff

# messages.getDiff

Returns complete messenger state diff and credentials for synchronizing official mobile applications and modern clients.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `ts` | integer | Starting synchronization timestamp. |
| `lp_version` | integer | LongPoll version. |
| `events_limit` | integer | Max events limit. Default: `1000`. |
| `msgs_limit` | integer | Max messages limit. Default: `1000`. |

### Result

Returns an object containing:
* `server_time` (integer) — Current server Unix timestamp;
* `server_version` (integer) — Server version;
* `invalidate_all` (boolean) — Invalidation flag;
* `conversations_info` (array) — Conversations with diffs and last messages;
* `profiles` (array) — User profile objects;
* `groups` (array) — Community profile objects;
* `counters` (object) — Unread counters;
* `credentials` (object) — LongPoll credentials (`server_lp`, `key`, `ts`).

### Example Request
```http
POST /method/messages.getDiff HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```
