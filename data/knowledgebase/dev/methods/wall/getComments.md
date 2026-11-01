OpenVK-KB-Heading: wall.getComments

# wall.getComments

Returns a list of comments on a wall post.

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the wall owner (positive for user, negative for group). |
| `post_id` | integer | **Required.** Identifier of the wall post. |
| `need_likes` | integer | `1` — return likes information for comments. Default: `1`. |
| `offset` | integer | Offset needed to return a specific subset of comments. Default: `0`. |
| `count` | integer | Number of comments to return. Default: `10`. |
| `sort` | string | Sort order: `"asc"` — chronological (oldest first), `"desc"` — reverse chronological (newest first). Default: `"asc"`. |
| `extended` | boolean / integer | `1` — return author profiles and groups (`profiles`, `groups`), `0` — comments only. Default: `0`. |
| `fields` | string | Comma-separated list of profile fields to return. |

### Result

Returns an object with the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `count` | integer | Total comment count on the post. |
| `items` | array | Array of comment objects. |
| `profiles` | array | Authors' user profiles (when `extended=1`). |
| `groups` | array | Authors' communities (when `extended=1`). |

### Possible Errors

| Code | Description |
| --- | --- |
| `15` | `Access denied` — access restricted by privacy settings. |
| `100` | `One of the parameters specified was missing or invalid` — post not found or deleted. |

### Example Request
```http
POST /method/wall.getComments HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&post_id=10&count=5&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 25,
                "from_id": 2,
                "date": 1775651000,
                "text": "Great news!",
                "likes": {
                    "count": 3,
                    "user_likes": 0,
                    "can_like": 1
                }
            }
        ]
    }
}
```
