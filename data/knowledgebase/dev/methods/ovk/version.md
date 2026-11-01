OpenVK-KB-Heading: ovk.version

# ovk.version

Returns the OpenVK engine version string running on the server.

### Authorization
This method is public and does not require authorization.

### Parameters
This method takes no parameters.

### Result

Returns the engine version string (e.g. `"1.0.0"`).

### Request Example
```http
POST /method/ovk.version HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

v=5.138
```

### Response Example
```json
{
    "response": "1.0.0"
}
```
