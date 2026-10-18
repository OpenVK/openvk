OpenVK-KB-Heading: account.setOnline

# account.setOnline

Marks the current user as online for 5 minutes and updates the client platform.

### Authorization
This method requires user authorization (`access_token`).

### Parameters
This method does not accept any parameters.

### Result
Returns `1` on success.

### Example Request
```http
POST /method/account.setOnline HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```