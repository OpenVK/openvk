OpenVK-KB-Heading: friends.areFriends

# friends.areFriends

Returns friendship status information between the current user and specified users.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_ids` | string | **Required parameter**. Comma-separated list of target user IDs (e.g. `1,2,3`). |

### Result

Returns an array of objects, each containing:
* `user_id` (integer) — Target user ID;
* `friend_status` (integer) — Friendship status:
  * `0` — Not friends, no pending requests;
  * `1` — Outgoing friend request sent (subscribed to this user);
  * `2` — Incoming friend request received (this user is subscribed to current user);
  * `3` — Mutual friends.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |

### Request Example
```http
POST /method/friends.areFriends HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_ids=2,3,4&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": [
        {
            "user_id": 2,
            "friend_status": 3
        },
        {
            "user_id": 3,
            "friend_status": 1
        },
        {
            "user_id": 4,
            "friend_status": 0
        }
    ]
}
```
