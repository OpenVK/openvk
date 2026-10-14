OpenVK-KB-Heading: account.getPushSettings

# account.getPushSettings

Returns current push notification settings for a device or specific conversation.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `token` | string | Device push token. |
| `peer_id` | integer | Conversation / chat peer ID. If omitted, global settings are returned. |

### Result
Returns an object containing push settings:

| Field | Type | Description |
| --- | --- | --- |
| `disabled_until` | integer | Unix timestamp until which notifications are disabled (`-1` — disabled forever, `0` — enabled). |
| `sound` | integer | `1` if sound is enabled, `0` if muted. |

### Example Request
```http
POST /method/account.getPushSettings HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

token=DEVICE_PUSH_TOKEN_HERE&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "disabled_until": 0,
        "sound": 1
    }
}
```
