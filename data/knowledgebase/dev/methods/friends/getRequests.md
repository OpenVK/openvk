OpenVK-KB-Heading: friends.getRequests

# friends.getRequests

Returns a list of incoming or outgoing friend requests for the current user.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `out` | integer | `1` to return outgoing requests (current user subscriptions), `0` to return incoming requests (current user followers). Default: `0`. |
| `fields` | string | Comma-separated list of additional profile fields (e.g. `sex,bdate,city,country,photo_50`). |
| `offset` | integer | Offset needed to return a specific subset of requests. Default: `0`. |
| `count` | integer | Number of requests to return (must be strictly less than `1000`). Default: `100`. |
| `extended` | integer | Extended format flag (for compatibility). Default: `0`. |
| `suggested` | integer | `1` to return suggested friend requests (returns an empty list in current version), `0` for regular requests. Default: `0`. |
| `need_messages` | integer | For API versions below 5.0: `1` to return request message text, `0` otherwise. Default: `0`. |
| `need_mutual` | integer | For API versions below 5.0: `1` to return mutual friends object `mutual`, `0` otherwise. Default: `0`. |

### Result

In API version 5.0 and higher, returns an object containing:
* `count` (integer) — Total number of followers / requests;
* `items` (array) — Array of [User](/dev/models/user) profile objects containing an extra `user_id` field.

In API versions below 5.0, returns an array of request objects:
* `uid` (integer) — User ID;
* `user_id` (integer) — User ID;
* `message` (string, optional) — Attached message text (if `need_messages=1`);
* `mutual` (object, optional) — Mutual friends object (if `need_mutual=1`).

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `100` | `One of the required parameters was not passed or is invalid.` — `count` exceeds the maximum allowed limit (1000). |

### Request Example
```http
POST /method/friends.getRequests HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

out=0&count=20&fields=sex,bdate&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 15,
                "user_id": 15,
                "first_name": "Ivan",
                "last_name": "Smirnov",
                "sex": 2,
                "bdate": "20.4.1998"
            }
        ]
    }
}
```
