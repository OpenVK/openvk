OpenVK-KB-Heading: status.get

# status.get

Returns current text status (including broadcast audio track if enabled) of a user or community description.

### Authorization
Does not require authorization if `user_id` or `group_id` is supplied. When called without parameters, returns the current authenticated user's status.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | User ID (positive number) or community ID (negative number). Default: current user ID. |
| `group_id` | integer | Community ID whose status/description is requested. |

### Result

Returns an object containing:
| Field | Type | Description |
| --- | --- | --- |
| `text` | string | User status text or community description. |
| `audio` | object | Audio track object being broadcasted to status (if active). |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized when called without parameters. |
| `15` | `Access denied` / `Invalid user` — User or community not found, deleted, or access restricted by privacy settings. |

### Example Request
```http
POST /method/status.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "text": "Have a great day!",
        "audio": {
            "id": 10,
            "owner_id": 1,
            "artist": "Queen",
            "title": "Bohemian Rhapsody",
            "duration": 354,
            "url": "https://openvk.instance/audio/1_10.mp3"
        }
    }
}
```
