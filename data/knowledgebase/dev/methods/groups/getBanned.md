OpenVK-KB-Heading: groups.getBanned

# groups.getBanned

Returns the list of users banned in a community (community blacklist).

### Authorization
This method requires user authorization (`access_token`) with community administrator privileges.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | **Required parameter**. Community ID. |
| `fields` | string | Comma-separated list of additional user profile fields (e.g. `sex,bdate,city,country,photo_50`). |
| `offset` | integer | Offset needed to return a specific subset of banned users. Default: `0`. |
| `count` | integer | Number of users to return. Default: `20`. |

### Result

Returns an object containing:
* `count` (integer) — Total number of banned users;
* `items` (array) — Array of [User](/dev/models/user) profile objects.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Access denied` — Current user is not an administrator of this community. |

### Request Example
```http
POST /method/groups.getBanned HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&count=10&fields=sex,bdate&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 5,
                "first_name": "Spammer",
                "last_name": "Ivanov",
                "sex": 2,
                "bdate": "1.1.2000"
            }
        ]
    }
}
```
