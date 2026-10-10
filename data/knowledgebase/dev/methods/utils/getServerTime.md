OpenVK-KB-Heading: utils.getServerTime

# utils.getServerTime

Returns current server time in unixtime format (seconds elapsed since January 1, 1970).

### Authorization
This method does not require authorization.

### Parameters
This method does not take any parameters.

### Result

Returns an `integer` — current server unixtime timestamp.

### Example Request
```http
POST /method/utils.getServerTime HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

v=5.138
```

### Example Response
```json
{
    "response": 1775656800
}
```
