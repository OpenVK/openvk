OpenVK-KB-Heading: friends.getOnline

# friends.getOnline

Returns a list of IDs of a user's friends who are currently online.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | User ID whose online friends are to be returned. If not specified, the current user ID is used. Default: `0`. |
| `online_mobile` | integer | Flag for returning mobile online friend IDs (for compatibility). Default: `0`. |

### Result

Returns an array of integer user IDs (`array of integers`) who are currently online.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Access denied: this user chose to hide his friends.` — The user hid their friends list via privacy settings. |
| `100` | `Invalid user` — User not found, deleted, or banned. |

### Request Example
```http
POST /method/friends.getOnline HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": [
        2,
        5,
        18
    ]
}
```
