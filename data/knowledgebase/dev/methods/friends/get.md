OpenVK-KB-Heading: friends.get

# friends.get

Returns a list of friend IDs of a user or detailed profile objects.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | User ID whose friends are to be returned. If not specified, the current user ID is used. Default: `0`. |
| `fields` | string | Comma-separated list of additional profile fields (e.g. `sex,bdate,city,country,photo_50`). If passed, the method returns an array of user objects; otherwise an array of integer IDs. |
| `offset` | integer | Offset needed to return a specific subset of friends. Default: `0`. |
| `count` | integer | Number of friends to return. Default: `100`. |

### Result

In API version 5.0 and higher, returns an object containing:
* `count` (integer) — Total number of friends of the user;
* `items` (array) — Array of integer friend IDs (if `fields` was not specified) or array of [User](/dev/models/user) profile objects (if `fields` was specified).

In API versions below 5.0, returns an array of IDs or user objects directly.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Access denied: this user chose to hide his friends.` — The user hid their friends list via privacy settings. |
| `100` | `Invalid user` — User not found, deleted, or banned. |

### Request Example
```http
POST /method/friends.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&count=2&fields=sex,bdate&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 42,
        "items": [
            {
                "id": 2,
                "first_name": "Pavel",
                "last_name": "Durov",
                "sex": 2,
                "bdate": "10.10.1984"
            },
            {
                "id": 3,
                "first_name": "Anna",
                "last_name": "Kuznetsova",
                "sex": 1,
                "bdate": "15.5.1995"
            }
        ]
    }
}
```
