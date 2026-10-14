OpenVK-KB-Heading: account.setOffline

# account.setOffline

Marks the current user as offline.

### Authorization
This method requires user authorization (`access_token`).

### Parameters
This method does not accept any parameters.

### Result
Returns `1` on success.

### Example Request
```http
POST /method/account.setOffline HTTP/1.1
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
