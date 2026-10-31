OpenVK-KB-Heading: account.getBanned

# account.getBanned

Returns a list of users added to the current user's blacklist.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `offset` | integer | Offset needed to return a specific subset of the blacklist. Default: `0`. |
| `count` | integer | Number of users to return (maximum `100`). Default: `100`. |
| `fields` | string | Comma-separated list of additional profile fields to return (e.g. `photo_50`, `sex`, `screen_name`). |

### Result
Returns an object containing the blacklist results:

| Field | Type | Description |
| --- | --- | --- |
| `count` | integer | Total number of blacklisted users. |
| `items` | array | Array of blacklisted user profile objects. |

### Example Request
```http
POST /method/account.getBanned HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

offset=0&count=20&fields=photo_50,screen_name&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 2,
                "first_name": "Ivan",
                "last_name": "Ivanov",
                "screen_name": "id2",
                "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png"
            }
        ]
    }
}
```
