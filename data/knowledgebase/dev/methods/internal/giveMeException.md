OpenVK-KB-Heading: internal.giveMeException

# internal.giveMeException

Triggers a runtime exception on the server side. Used for testing internal server error interception, logging, and error response formatting.

### Authorization
This method does not require user authorization.

### Parameters
This method takes no parameters.

### Result
This method does not return a successful response; it intentionally throws a `RuntimeException` (`"Test exception for server error interception"`).

### Example Request
```http
POST /method/internal.giveMeException HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

v=5.138
```

### Example Response
```json
{
  "error_code": 10,
  "error_msg": "Internal server error: could not process request",
}
```
