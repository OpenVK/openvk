OpenVK-KB-Heading: wall.getComment

# wall.getComment

Returns detailed information about a single comment on a wall post.

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the wall owner. |
| `comment_id` | integer | **Required.** Identifier of the comment. |
| `extended` | boolean / integer | `1` — return author profiles and groups (`profiles`, `groups`), `0` — comment only. Default: `0`. |
| `fields` | string | Comma-separated list of profile fields to return. |

### Result

Returns an object with an `items` array containing the requested comment, along with `can_post` and `show_reply_button` flags.

### Example Request
```http
POST /method/wall.getComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&comment_id=25&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "items": [
            {
                "id": 25,
                "from_id": 2,
                "date": 1775651000,
                "text": "Great news!",
                "post_id": 10,
                "owner_id": 1,
                "likes": {
                    "count": 3,
                    "user_likes": 0,
                    "can_like": 1
                }
            }
        ],
        "can_post": true,
        "show_reply_button": true
    }
}
```
