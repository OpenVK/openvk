OpenVK-KB-Heading: account.unregisterDevice

# account.unregisterDevice

Unregisters a device from receiving push notifications.

> **Note (compatibility stub):** This method is a stub for VK clients compatibility and always returns `1`.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `token` | string | Push notification device token to unregister. **Required.** |

### Result
Returns `1` on success.

### Example Request
```http
POST /method/account.unregisterDevice HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

token=DEVICE_PUSH_TOKEN_HERE&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
