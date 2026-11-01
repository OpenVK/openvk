OpenVK-KB-Heading: photos.getComments

# photos.getComments

Returns a list of comments on a photo.

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Photo owner ID (positive for user, negative for community). |
| `photo_id` | integer | Photo ID. |
| `pid` | integer | Alternative photo ID parameter (for backwards compatibility). |
| `need_likes` | boolean | `1` — return likes info for each comment, `0` — do not return. Default: `0`. |
| `offset` | integer | Offset needed to return a specific subset of comments. Default: `0`. |
| `count` | integer | Number of comments to return. Default: `100`. |
| `sort` | string | Sort order: `asc` — chronological, `desc` — reverse chronological. Default: `asc`. |
| `extended` | boolean | `1` — return comment authors profile objects in `profiles`, `0` — do not return. Default: `0`. |
| `fields` | string | Comma-separated list of additional profile fields (when `extended=1`). |

### Result

In API version 5.0 and above, returns an object containing:
* `count` (integer) — total number of comments on the photo;
* `items` (array) — array of comment objects;
* `profiles` (array, optional) — array of author profiles (if `extended=1`).

In API versions below 5.0, returns an array starting with total count followed by comment objects (`[count, comment1, comment2, ...]`).

Each comment object contains:
| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Comment ID. |
| `from_id` | integer | Author ID. |
| `date` | integer | Comment creation time (Unix timestamp). |
| `text` | string | Comment text. |
| `reply_to_comment` | integer | Parent comment ID (if this comment is a reply). |
| `attachments` | array | Array of attachments (stickers and media). |
| `likes` | object | Comment likes object (if `need_likes=1`). |

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Photo not found, deleted, or access restricted by privacy settings. |

### Example Request
```http
POST /method/photos.getComments HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&photo_id=1&need_likes=1&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "from_id": 2,
                "date": 1609459200,
                "text": "Great photo!",
                "reply_to_comment": null,
                "attachments": [],
                "likes": {
                    "count": 3,
                    "user_likes": 0,
                    "can_like": 1
                }
            }
        ],
        "profiles": [
            {
                "id": 2,
                "first_name": "Pavel",
                "last_name": "Durov"
            }
        ]
    }
}
```
