OpenVK-KB-Heading: friends.edit

# friends.edit

Edits friend list assignments for a specific friend.

> **Note:** In the current version of OpenVK, this method serves as a compatibility stub and returns `1`.

### Authorization
This method requires user authorization (`access_token`).

### Parameters
This method requires no mandatory parameters.

### Result

Returns integer `1` upon success.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |

### Request Example
```http
POST /method/friends.edit HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
