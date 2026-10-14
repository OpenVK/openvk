OpenVK-KB-Heading: ovk.getMirrors

# ovk.getMirrors

Returns the list of configured domain mirrors for the current OpenVK instance.

### Authorization
This method is public and does not require authorization.

### Parameters
This method takes no parameters.

### Result

Returns an array of strings representing domain mirror hostnames (e.g. `["openvk.su", "ovk.to"]`).

### Request Example
```http
POST /method/ovk.getMirrors HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

v=5.138
```

### Response Example
```json
{
    "response": [
        "openvk.su",
        "ovk.to"
    ]
}
```
