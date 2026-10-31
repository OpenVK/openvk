OpenVK-KB-Heading: account.registerDevice

# account.registerDevice

Registers a client device for push notification delivery.

> **Note (compatibility stub):** This method is implemented as a stub for compatibility with VK mobile clients. It accepts parameters and always returns `1`.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `token` | string | Push notification device token. **Required.** |
| `device_model` | string | Device model string (e.g. `iPhone 13` or `Pixel 7`). |
| `device_year` | string | Device release year. |
| `system_version` | string | Operating system version. |
| `settings` | string | Serialized JSON string containing push notification settings. |

### Result
Returns `1` on success.

### Example Request
```http
POST /method/account.registerDevice HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

token=DEVICE_PUSH_TOKEN_HERE&device_model=Pixel+7&system_version=Android+14&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
