OpenVK-KB-Heading: newsfeed.getComments

# newsfeed.getComments

Returns a list of wall posts with recent comments made on followed walls or discussions, or posts where the user previously commented.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `count` | integer | Number of posts to return (1 to 100). Default: `30`. |
| `filters` | string | Item types (currently supports: `post`). Default: `post`. |
| `reposts` | string | Repost IDs for filtering. |
| `start_time` | integer | Earliest comment timestamp (Unix time). Default: `0`. |
| `end_time` | integer | Latest comment timestamp (Unix time). |
| `last_comments` | integer | Flag to include recent comments in post objects. Default: `1`. |
| `last_comments_count` | integer | Number of recent comments to return per post. Default: `1`. |
| `start_from` | string | Offset or cursor identifier for pagination. |
| `fields` | string | Comma-separated list of additional profile and group fields. |
| `offset` | integer | Items offset (0 to 1000). Default: `0`. |

### Result

Returns an object containing:
* `items` (array) — array of post objects (`type: "post"`). In each post, the `comments` object includes:
  * `count` (integer) — total count of comments for the post;
  * `can_post` (integer) — `1` if the current user can post comments;
  * `list` (array) — array of recent comment objects (`id`, `uid`, `text`, `date`).
* `profiles` (array) — array of profiles of post and comment authors;
* `groups` (array) — array of communities;
* `next_from` (string) — cursor for the next page;
* `new_from` (string) — offset string.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Request Example
```http
POST /method/newsfeed.getComments HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=5&last_comments_count=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "items": [
            {
                "id": 15,
                "owner_id": 1,
                "from_id": 1,
                "date": 1699990000,
                "text": "Discussing the new platform update",
                "type": "post",
                "source_id": 1,
                "post_id": 15,
                "comments": {
                    "count": 5,
                    "can_post": 1,
                    "list": [
                        {
                            "id": 48,
                            "uid": 2,
                            "text": "Great news!",
                            "date": 1699991200
                        }
                    ]
                },
                "likes": {
                    "count": 10,
                    "user_likes": 1,
                    "can_publish": 1
                }
            }
        ],
        "profiles": [
            {
                "id": 2,
                "uid": 2,
                "first_name": "Pavel",
                "last_name": "Durov",
                "screen_name": "durov",
                "photo": "/assets/packages/static/openvk/img/camera_50.png",
                "photo_50": "/assets/packages/static/openvk/img/camera_50.png",
                "photo_100": "/assets/packages/static/openvk/img/camera_100.png",
                "online": 1
            }
        ],
        "groups": [],
        "next_from": "5",
        "new_from": "5"
    }
}
```
