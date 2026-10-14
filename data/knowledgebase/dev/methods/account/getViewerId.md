OpenVK-KB-Heading: account.getViewerId

# account.getViewerId

Returns the ID of the current authorized user.

### Authorization
This method requires user authorization (`access_token`).

### Parameters
This method does not accept any parameters.

### Result
Returns an integer representing the current user ID.

### Example Request
```http
POST /method/account.getViewerId HTTP/1.1
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
