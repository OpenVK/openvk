OpenVK-KB-Heading: friends.delete

# friends.delete

Removes a user from the friends list (demoting them to a follower).

### Authorization
This method requires user authorization (`access_token`) with `friends` permissions.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | **Required parameter**. ID of the user to remove from friends. |

### Result

In API version 5.0 and higher, returns integer `1` upon successful deletion.

In API versions below 5.0, returns an object:
```json
{
    "success": 1
}
```

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Access denied: No friend or friend request found.` — The specified user is not in the friends list. |
| `100` | `Invalid user` — User with the specified ID was not found. |

### Request Example
```http
POST /method/friends.delete HTTP/1.1
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
