OpenVK-KB-Heading: account.get

# account.get

Returns basic profile structures for the specified users or the current account.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_ids` | string | Comma-separated list of user IDs. If omitted, returns the current user. |
| `fields` | string | List of additional profile fields. |

### Result
Returns an array of user objects:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | User ID. |
| `first_name` | string | First name. |
| `last_name` | string | Last name. |
| `screen_name` | string | Short domain address. |
| `photo_50` | string | 50x50px avatar URL. |
| `photo_100` | string | 100x100px avatar URL. |
| `photo_base` | string | Source avatar URL. |
| `has_photo` | integer | `1` if user has an uploaded photo. |
| `verified` | integer | `1` if account is verified. |
| `sex` | integer | Sex: `1` for female, `2` for male, `0` if unspecified. |

### Example Request
```http
POST /method/account.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": [
        {
            "id": 1,
            "first_name": "Pavel",
            "last_name": "Durov",
            "screen_name": "durov",
            "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
            "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
            "photo_base": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png",
            "has_photo": 1,
            "verified": 1,
            "sex": 2
        }
    ]
}
```
