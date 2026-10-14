OpenVK-KB-Heading: notifications.markAsViewed

# notifications.markAsViewed

Resets the unviewed notification counter of the current user, marking all existing notifications as viewed.

### Authorization
This method requires user authorization (`access_token`). Executes a write action.

### Parameters
This method takes no parameters.

### Result

Returns `1` on successful notification offset update, or `0` on failure.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Request Example
```http
POST /method/notifications.markAsViewed HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
