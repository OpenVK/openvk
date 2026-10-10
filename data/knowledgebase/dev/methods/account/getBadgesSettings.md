OpenVK-KB-Heading: account.getBadgesSettings

# account.getBadgesSettings

Returns badge settings and states for the client application.

> **Note (compatibility stub):** Badge icons system is disabled in OpenVK. The method returns `{"items": [], "is_enabled": false}`.

### Authorization
This method requires user authorization (`access_token`).

### Parameters
This method does not accept any parameters.

### Result
Returns an object containing badge configuration:

| Field | Type | Description |
| --- | --- | --- |
| `items` | array | Array of badge items (`[]`). |
| `is_enabled` | boolean | Whether badges are enabled (`false`). |

### Example Request
```http
POST /method/account.getBadgesSettings HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "items": [],
        "is_enabled": false
    }
}
```
