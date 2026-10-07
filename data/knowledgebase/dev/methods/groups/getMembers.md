OpenVK-KB-Heading: groups.getMembers

# groups.getMembers

Returns a list of community members (followers).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | **Required parameter**. Community ID. |
| `fields` | string | Comma-separated list of additional user profile fields (e.g. `sex,bdate,city,country,photo_50`). |
| `offset` | integer | Offset needed to return a specific subset of members. Default: `0`. |
| `count` | integer | Number of members to return. Default: `10`. |

### Result

Returns an object containing:
* `count` (integer) — Total number of community members;
* `items` (array) — Array of [User](/dev/models/user) profile objects.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Access denied` — Access to view this community is restricted. |

### Request Example
```http
POST /method/groups.getMembers HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&count=2&fields=sex,bdate&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 150,
        "items": [
            {
                "id": 1,
                "first_name": "Pavel",
                "last_name": "Durov",
                "sex": 2,
                "bdate": "10.10.1984"
            }
        ]
    }
}
```
