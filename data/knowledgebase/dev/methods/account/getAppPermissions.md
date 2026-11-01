OpenVK-KB-Heading: account.getAppPermissions

# account.getAppPermissions

Returns a bitmask of application settings and granted access permissions.

> **Note:** In the current implementation, this method returns a fixed bitmask value `9355263` (full permission mask) to ensure compatibility with third-party and official VK clients.

### Authorization
This method can be called without user authorization.

### Parameters
This method does not accept any parameters.

### Result
Returns an integer representing the permission bitmask.

### Example Request
```http
POST /method/account.getAppPermissions HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

v=5.138
```

### Example Response
```json
{
    "response": 9355263
}
```
