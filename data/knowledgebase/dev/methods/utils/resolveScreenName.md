OpenVK-KB-Heading: utils.resolveScreenName

# utils.resolveScreenName

Determines the object type (user or group) and its numeric ID by a screen name or short URL alias.

### Authorization
This method does not require authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `screen_name` | string | **Required.** Screen name of a user or community (e.g., `durov`, `id1`, `club12`, `apiclub`). |

### Result

Returns an object with the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `object_id` | integer | Numeric identifier of the user or community. |
| `type` | string | Object type: `"user"` (user) or `"group"` (community). |

### Possible Errors

| Code | Description |
| --- | --- |
| `104` | `Not found` — object with the specified screen name was not found. |

### Example Request
```http
POST /method/utils.resolveScreenName HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

screen_name=apiclub&v=5.138
```

### Example Response
```json
{
    "response": {
        "object_id": 1,
        "type": "group"
    }
}
```
