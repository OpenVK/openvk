OpenVK-KB-Heading: messages.getLongPollServer

# messages.getLongPollServer

Returns connection parameters for the LongPoll server to receive real-time updates and incoming messages without polling.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `need_pts` | integer | `1` — return `pts` parameter to track event state. Default: `0`. |
| `lp_version` | integer | LongPoll protocol version (e.g. `2`, `3`, `19`). Default: `2`. |
| `use_ssl` | integer | `1` — return HTTPS URL. Default: `0`. |
| `group_id` | integer | Community ID (if connecting for community bot). |

### Result

Returns an object containing:
* `server` (string) — LongPoll server hostname and path;
* `key` (string) — Secret session key;
* `ts` (integer) — Starting timestamp / sequence number;
* `pts` (integer, optional) — Event sequence number (when `need_pts=1`).

### Example Request
```http
POST /method/messages.getLongPollServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

need_pts=1&lp_version=3&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "key": "4f9a0c7e2b1d",
        "server": "openvk.instance/lp",
        "ts": 1696680000,
        "pts": 450
    }
}
```
