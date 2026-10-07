OpenVK-KB-Heading: friends.search

# friends.search

Searches through a user's friends list by name or surname substring.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `q` | string | **Required parameter**. Search query string (substring of first or last name). |
| `user_id` | integer | ID of the user whose friends are to be searched. If not specified, the current user ID is used. Default: `0`. |
| `fields` | string | Comma-separated list of additional profile fields (e.g. `sex,bdate,city,country,photo_50`). If specified, returns an array of user objects; otherwise an array of integer IDs. |
| `offset` | integer | Offset needed to return a specific subset of friends. Default: `0`. |
| `count` | integer | Number of friends to return. Default: `100`. |

### Result

Returns an object containing:
* `count` (integer) — Total number of matching friends found;
* `items` (array) — Array of integer friend IDs or array of [User](/dev/models/user) profile objects (if `fields` was specified).

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Access denied: this user chose to hide his friends.` — The user hid their friends list via privacy settings. |
| `100` | `Invalid user` — User not found, deleted, or banned. |

### Request Example
```http
POST /method/friends.search HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=Ivan&fields=sex,bdate&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 15,
                "first_name": "Ivan",
                "last_name": "Smirnov",
                "sex": 2,
                "bdate": "20.4.1998"
            }
        ]
    }
}
```
