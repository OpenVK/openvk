OpenVK-KB-Heading: newsfeed.getGlobal

# newsfeed.getGlobal

Returns the global newsfeed of all public wall posts across the entire OpenVK instance, taking into account privacy settings, hidden sources, and NSFW tolerance. Also supports outputting feed in RSS 2.0 format.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `fields` | string | Comma-separated list of additional profile and group fields to return. |
| `start_from` | string | Cursor for pagination in the format `timestamp_id` (from previous `next_from`). |
| `start_time` | integer | Earliest timestamp (Unix time) to fetch posts from. Default: `0`. |
| `end_time` | integer | Latest timestamp (Unix time) to fetch posts up to. |
| `offset` | integer | Items offset. Default: `0`. |
| `count` | integer | Number of posts to return. Default: `30`. |
| `extended` | boolean | `1` (`true`) — return `profiles` and `groups` arrays, `0` (`false`) — return posts only. Default: `1`. |
| `rss` | boolean | `1` (`true`) — generate and return an RSS 2.0 XML feed channel, `0` (`false`) — return standard JSON response. Default: `0`. |
| `return_banned` | boolean | `1` (`true`) — include posts from hidden/ignored sources, `0` (`false`) — filter out ignored sources. Default: `0`. |
| `with_alien_wall_posts` | boolean | `1` (`true`) — include third-party posts on walls, `0` (`false`) — return posts by wall owners only. Default: `0`. |

### Result

Returns an object containing:
* `items` (array) — array of post objects (`type: "post"`);
* `profiles` (array) — array of user profiles (when `extended=1`);
* `groups` (array) — array of communities (when `extended=1`);
* `next_from` (string) — cursor for the next page.

When `rss=1`, an RSS channel object is returned instead.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Request Example
```http
POST /method/newsfeed.getGlobal HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=10&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "items": [
            {
                "id": 204,
                "owner_id": 1,
                "from_id": 1,
                "date": 1700005000,
                "text": "Global news for all users",
                "type": "post",
                "source_id": 1,
                "comments": {
                    "count": 2,
                    "can_post": 1
                },
                "likes": {
                    "count": 10,
                    "user_likes": 0,
                    "can_like": 1
                }
            }
        ],
        "profiles": [
            {
                "id": 1,
                "first_name": "Administrator",
                "last_name": "",
                "screen_name": "admin",
                "photo_50": "/assets/packages/static/openvk/img/camera_50.png",
                "photo_100": "/assets/packages/static/openvk/img/camera_100.png"
            }
        ],
        "groups": [],
        "next_from": "1700005000_204"
    }
}
```
