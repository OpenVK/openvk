OpenVK-KB-Heading: messages.getInviteLink

# messages.getInviteLink

Generates or returns an existing invite link to join a multi-user chat.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `peer_id` | integer | Destination ID (`2000000000 + chat_id`). **Required.** |
| `reset` | integer | `1` — regenerate/invalidate existing link, `0` — return current link. Default: `0`. |

### Result

Returns an object containing:
* `link` (string) — Invite URL (e.g. `https://openvk.instance/join/abc123...`).

### Example Request
```http
POST /method/messages.getInviteLink HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&reset=0&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
