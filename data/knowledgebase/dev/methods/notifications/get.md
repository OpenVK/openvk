OpenVK-KB-Heading: notifications.get

# notifications.get

Returns a list of notifications (replies, mentions, likes, invites) for the current user.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `count` | integer | Number of notifications to return (maximum 100). Default: `10`. |
| `offset` | integer | Offset from the beginning of the list. Default: `0`. |
| `start_from` | string | Cursor identifier for pagination. |
| `filters` | string | Comma-separated list of notification type filters. |
| `start_time` | integer | Earliest timestamp (Unix time). Default: `0`. |
| `end_time` | integer | Latest timestamp (Unix time). |
| `archived` | integer | Flag indicating whether to include archived notifications (`1` — included, `0` — unread only). Default: `1`. |

### Result

Returns an object containing:
* `items` (array) — array of notification objects;
* `profiles` (array) — array of user profiles involved in notifications;
* `groups` (array) — array of communities;
* `last_viewed` (integer) — offset of the last viewed notification.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `125` | `Count is too big` — `count` parameter exceeds maximum allowed limit of 100. |

### Request Example
```http
POST /method/notifications.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=5&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "items": [
            {
                "type": "wall_reply",
                "date": 1700002000,
                "feedback": {
                    "count": 1,
                    "items": [
                        {
                            "id": 10,
                            "from_id": 2,
                            "text": "Great post!"
                        }
                    ]
                }
            }
        ],
        "profiles": [
            {
                "id": 2,
                "first_name": "Pavel",
                "last_name": "Durov",
                "photo_50": "/assets/packages/static/openvk/img/camera_50.png"
            }
        ],
        "groups": [],
        "last_viewed": 10
    }
}
```
