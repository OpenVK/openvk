OpenVK-KB-Heading: newsfeed.addBan

# newsfeed.addBan

Hides posts of specified users or communities from the current user's newsfeed.

### Authorization
This method requires user authorization (`access_token`). Executes a write action.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_ids` | string | Comma-separated list of user IDs (positive numbers). |
| `group_ids` | string | Comma-separated list of community IDs (positive numbers). |

> **Important:** The total number of IDs in a single request cannot exceed 10. The total number of ignored sources is bounded by server configuration (default 50).

### Result

Returns `1` on success, or `0` if the provided list of IDs is empty.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `-10` | `Limit of 'ids' is 10` — Maximum 10 IDs allowed per request. |
| `-50` | `Ignoring limit exceeded` — Total ignored sources limit reached. |

### Request Example
```http
POST /method/newsfeed.addBan HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_ids=5,10&group_ids=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
