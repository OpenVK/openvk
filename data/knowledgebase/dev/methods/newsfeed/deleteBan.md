OpenVK-KB-Heading: newsfeed.deleteBan

# newsfeed.deleteBan

Removes users or communities from the ignored sources list, restoring visibility of their posts in the current user's newsfeed.

### Authorization
This method requires user authorization (`access_token`). Executes a write action.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_ids` | string | Comma-separated list of user IDs. |
| `group_ids` | string | Comma-separated list of community IDs. |

> **Important:** The total number of IDs in a single request cannot exceed 10.

### Result

Returns `1` on success, or `0` if the provided list of IDs is empty.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `-10` | `Limit of ids is 10` — Maximum 10 IDs allowed per request. |

### Request Example
```http
POST /method/newsfeed.deleteBan HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_ids=5&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
