OpenVK-KB-Heading: wall.getNearby

# wall.getNearby

Returns a list of geo-tagged posts published nearby a specified wall post.

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the wall owner (positive for user, negative for group). |
| `post_id` | integer | **Required.** Identifier of the source geo-tagged post. |

### Result

Returns an array of post summary objects published near the source location:

| Field | Type | Description |
| --- | --- | --- |
| `message` | string | Post preview text. |
| `url` | string | Relative URL to the post. |
| `created` | string | Formatted creation timestamp. |
| `owner` | object | Post author details (`domain`, `photo_50`, `name`, `verified`). |
| `geo` | object | Geographic location data. |
| `distance` | number | Distance from the source post coordinates. |

### Possible Errors

| Code | Description |
| --- | --- |
| `15` | `Access denied` — post is restricted by privacy settings. |
| `100` | `One of the parameters specified was missing or invalid: post_id is undefined` — post not found or deleted. |
| `-97` | `Post doesn't contains geo` — source post does not contain geolocation data. |

### Example Request
```http
POST /method/wall.getNearby HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&post_id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": [
        {
            "message": "Walking in the park",
            "url": "/wall1_43",
            "created": "just now",
            "owner": {
                "domain": "durov",
                "photo_50": "/assets/packages/static/openvk/img/camera_50.png",
                "name": "Pavel Durov",
                "verified": true
            },
            "geo": {
                "type": "point",
                "coordinates": "59.9343 30.3351"
            },
            "distance": 120.5
        }
    ]
}
```
