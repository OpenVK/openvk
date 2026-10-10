OpenVK-KB-Heading: users.getFollowers

# users.getFollowers

Returns a list of IDs or user profile objects for a specified user's followers.

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | **Required.** Identifier of the target user. |
| `fields` | string | Comma-separated list of additional profile fields to return. If specified, full user objects are returned instead of IDs. |
| `offset` | integer | Offset needed to return a specific subset of followers. Default: `0`. |
| `count` | integer | Number of followers to return. Default: `100`. |

### Result

Returns an object with the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `count` | integer | Total number of followers. |
| `items` | array | Array of numeric user IDs (or array of user objects if `fields` was specified). |

### Possible Errors

| Code | Description |
| --- | --- |
| `14` | `Invalid user` — user not found or deleted. |
| `15` | `Access denied` — access restricted by user's privacy settings. |

### Example Request
```http
POST /method/users.getFollowers HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&count=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 12,
        "items": [
            2,
            3
        ]
    }
}
```
