OpenVK-KB-Heading: account.getMulti

# account.getMulti

Returns information about the current multi-account session.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `fields` | string | List of additional profile fields. |

### Result
Returns an object containing multi-account session details:

| Field | Type | Description |
| --- | --- | --- |
| `count` | integer | Number of available accounts (`1`). |
| `items` | object | Current user object (`photo_50`, `photo_100`, `photo_base`, `has_photo`). |

### Example Request
```http
POST /method/account.getMulti HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 1,
        "items": {
            "id": 1,
            "first_name": "Pavel",
            "last_name": "Durov",
            "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
            "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png"
        }
    }
}
```
