OpenVK-KB-Heading: wall.get

# wall.get

Returns a list of posts from a user or community wall based on filters and pagination settings.

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the wall owner (positive for user, negative for group). |
| `domain` | string | Short screen name / domain of the user or community. |
| `offset` | integer | Offset needed to return a specific subset of posts. Default: `0`. |
| `count` | integer | Number of posts to return (maximum `100`). Default: `30`. |
| `extended` | integer | `1` — return additional `profiles` and `groups` arrays, `0` — posts only. Default: `0`. |
| `filter` | string | Filter type: `"all"` — all posts (default), `"owner"` — owner posts only, `"others"` — posts by others, `"archived"` — archived posts, `"suggests"` — suggested community posts. |
| `archive_year` | integer | Target year for archived posts (used when `filter=archived`). |

### Result

Returns an object with the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `count` | integer | Total number of posts matching the filter. |
| `items` | array | Array of wall post objects. |
| `profiles` | array | Array of authors' user profiles (when `extended=1`). |
| `groups` | array | Array of authors' communities (when `extended=1`). |

Each post object contains `id`, `owner_id`, `from_id`, publication timestamp `date`, `text`, `attachments`, `likes`, `comments`, `reposts`, and `is_pinned` status.

### Possible Errors

| Code | Description |
| --- | --- |
| `15` | `Access denied` — wall is private or disabled. |
| `18` | `User was deleted or banned` — target user has been deleted or banned. |

### Example Request
```http
POST /method/wall.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&count=2&filter=owner&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 1,
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
                    "user_likes": 1,
                    "can_like": 1
                },
                "reposts": {
                    "count": 1,
                    "user_reposted": 0
                }
            }
        ]
    }
}
```
