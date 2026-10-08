OpenVK-KB-Heading: internal.getNotifications

# internal.getNotifications

Returns broadcast service announcements and MOTD (Message of the Day) notifications used on startup by legacy official VK 2.x mobile applications. In OpenVK, this method currently functions as a stub returning an empty list.

### Authorization
This method does not require user authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `device` | string | Client device model or identifier. Default: `null`. |
| `os` | string | Operating system name and version. Default: `null`. |
| `app_version` | string / integer | Client application version string or number. Default: `null`. |
| `locale` | string | Client interface language code (e.g. `ru`, `en`). Default: `null`. |

### Result

Returns an array of notification objects (currently always an empty array `[]` in OpenVK).

### Example Request
```http
POST /method/internal.getNotifications HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

device=iphone&os=ios+4.3&app_version=2.0&locale=ru&v=5.138
```

### Example Response
```json
{
    "response": []
}
```
