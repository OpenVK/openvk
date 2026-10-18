OpenVK-KB-Heading: utils.resolveGuid

# utils.resolveGuid

Returns user profile information by their Chandler core unique identifier (GUID).

### Authorization
This method does not require authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `guid` | string | **Required.** Chandler core user unique identifier (GUID). |

### Result

Returns a standard user object structure (identical to `users.get`).

### Possible Errors

| Code | Description |
| --- | --- |
| `104` | `Not found` — user with the specified GUID was not found. |

### Example Request
```http
POST /method/utils.resolveGuid HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

guid=018e6e5f-1234-789a-bcde-f0123456789a&v=5.138
```

### Example Response
```json
{
    "response": {
        "id": 1,
        "first_name": "Pavel",
        "last_name": "Durov",
        "screen_name": "durov",
        "photo_50": "/assets/packages/static/openvk/img/camera_50.png",
        "photo_100": "/assets/packages/static/openvk/img/camera_100.png"
    }
}
```
