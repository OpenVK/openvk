OpenVK-KB-Heading: wall.getById

# wall.getById

Returns wall post objects by their identifiers.

### Authorization
This method does not require authorization when viewing publicly accessible posts.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `posts` | string | **Required.** Comma-separated post identifiers in `<owner_id>_<post_id>` format (e.g. `1_10,-5_42`). |
| `extended` | integer | `1` — return user and community info (`profiles`, `groups`), `0` — posts only. Default: `0`. |
| `fields` | string | Comma-separated list of profile and group fields to return (when `extended=1`). |

### Result

In API v5.x with `extended=0`, returns an array of post objects. With `extended=1`, returns an object:

| Field | Type | Description |
| --- | --- | --- |
| `items` | array | Array of post objects. |
| `profiles` | array | Profiles of authors and attachment owners. |
| `groups` | array | Communities of authors and attachment owners. |

### Example Request
```http
POST /method/wall.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

posts=1_10&extended=1&v=5.138
```

### Example Response
```json
{
    "response": {
        "items": [
            {
                "id": 10,
                "from_id": 1,
                "owner_id": 1,
                "date": 1775650000,
                "text": "Welcome to my page!",
                "comments": {
                    "count": 2,
                    "can_post": 1
                },
                "likes": {
                    "count": 5,
                    "user_likes": 0,
                    "can_like": 1
                },
                "reposts": {
                    "count": 1,
                    "user_reposted": 0
                }
            }
        ],
        "profiles": [
            {
                "id": 1,
                "first_name": "Pavel",
                "last_name": "Durov",
                "photo_50": "/assets/packages/static/openvk/img/camera_50.png"
            }
        ],
        "groups": []
    }
}
```
