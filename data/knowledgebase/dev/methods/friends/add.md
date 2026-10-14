OpenVK-KB-Heading: friends.add

# friends.add

Sends a friend request or approves an incoming friend request from the specified user.

### Authorization
This method requires user authorization (`access_token`) with `friends` permissions.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | **Required parameter**. ID of the user to send a request to or whose request is being accepted. |

### Result

Returns an integer operation status code:
* `1` — Friend request sent successfully (subscribed to the user's profile);
* `2` — Incoming friend request approved (users are now mutual friends) or users are already friends.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `9` | `Flood control: action too fast or too frequent.` — Rate limit exceeded for outgoing friend requests. |
| `174` | `Cannot add user himself as friend` — Cannot add oneself as a friend. |
| `177` | `Cannot add this user to friends as user not found` — User with the specified ID was not found. |

### Request Example
```http
POST /method/friends.add HTTP/1.1
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
