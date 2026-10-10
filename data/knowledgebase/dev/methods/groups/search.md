OpenVK-KB-Heading: groups.search

# groups.search

Searches for communities on the platform by keywords.

### Authorization
This method can be called with or without user authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `q` | string | **Required parameter**. Search query string. |
| `offset` | integer | Offset needed to return a specific subset of communities. Default: `0`. |
| `count` | integer | Number of communities to return (must be less than or equal to `100`). Default: `100`. |
| `fields` | string | Comma-separated list of additional community fields. Default: `"screen_name,is_admin,is_member,is_advertiser,photo_50,photo_100,photo_200"`. |

### Result

In API version 5.0 and higher, returns an object containing:
* `count` (integer) — Total number of communities found;
* `items` (array) — Array of [Group](/dev/models/group) objects.

In API versions below 5.0, returns an array formatted as `[count, group1, group2, ...]`.

### Possible Errors

| Code | Description |
| --- | --- |
| `100` | `One of the parameters specified was missing or invalid: count should be less or equal to 100` — `count` exceeds maximum allowed value of 100. |

### Request Example
```http
POST /method/groups.search HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=news&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 5,
                "name": "OpenVK News",
                "screen_name": "news",
                "is_closed": 0,
                "type": "page",
                "is_admin": 0,
                "is_member": 1,
                "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
                "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
                "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
            }
        ]
    }
}
```
