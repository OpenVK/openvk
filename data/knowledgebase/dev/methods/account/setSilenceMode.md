OpenVK-KB-Heading: account.setSilenceMode

# account.setSilenceMode

Disables sound alerts or push notifications for a specified duration for a given chat or globally.

### Authorization
This method requires user authorization (`access_token`). Rate limits for write actions apply.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `token` | string | Device push token. |
| `time` | integer | Silence mode duration in seconds: `-1` — mute forever, `0` — re-enable notifications. Default: `0`. |
| `peer_id` | integer | Conversation / chat peer ID. If `0` or omitted, applies globally. Default: `0`. |
| `sound` | integer | `1` — play sound with notifications, `0` — mute notification sounds. Default: `1`. |
| `disabled_mentions` | integer | `1` — disable notifications on direct mentions. Default: `0`. |
| `disabled_mass_mentions` | integer | `1` — disable notifications on mass mentions (`@all`, `@online`). Default: `0`. |

### Result
Returns `1` on success.

### Example Request
```http
POST /method/account.setSilenceMode HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&time=3600&sound=0&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
