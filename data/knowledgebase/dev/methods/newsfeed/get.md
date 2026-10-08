OpenVK-KB-Heading: newsfeed.get

# newsfeed.get

Returns a list of wall posts, photos, and videos for the current user's newsfeed, based on their subscriptions to users and communities.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `filters` | string | Comma-separated list of item types to return. Possible values: `post` (wall posts), `photo` (photos), `video` (videos). Default: `post`. |
| `fields` | string | Comma-separated list of additional profile and group fields to return (e.g. `sex, bdate, screen_name, photo_50, photo_100`). |
| `start_from` | string | Pagination cursor in the format `timestamp_id` (e.g., `1700000000_12345`), passed from the `next_from` field of a previous response. |
| `start_time` | integer | Earliest timestamp (Unix time) to fetch news items from. Default: `0`. |
| `end_time` | integer | Latest timestamp (Unix time) to fetch news items up to. |
| `offset` | integer | Offset within the candidate items list (0 to 1000). Default: `0`. |
| `count` | integer | Number of items to return (1 to 100). Default: `30`. |
| `extended` | boolean | `1` (`true`) — return additional `profiles` and `groups` arrays with author info, `0` (`false`) — return items only. Default: `1`. |
| `with_alien_wall_posts` | boolean | `1` (`true`) — include posts made by third parties on followed walls, `0` (`false`) — return posts by wall owners only. Default: `0`. |
| `forGodSakePleaseDoNotReportAboutMyOnlineActivity` | integer | Special flag: `1` avoids updating the user's online activity (on API versions > 5.63). Default: `0`. |

### Result

In API version 5.0 and higher, returns an object containing:
* `items` (array) — array of feed item objects (`post`, `photo`, or `video`);
* `profiles` (array) — array of user profiles (when `extended=1`);
* `groups` (array) — array of communities (when `extended=1`);
* `next_from` (string) — cursor for fetching the next page of news (used in `start_from`).

In legacy API versions (prior to version 5.0):
* `new_from` (string) and `new_offset` (integer) are provided;
* Elements in `items` include `post_id` and `source_id`, profiles contain `uid`, communities contain `gid`.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Request Example
```http
POST /method/newsfeed.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

filters=post,photo&count=10&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "items": [
            {
                "id": 105,
                "owner_id": 1,
                "source_id": 1,
                "from_id": 1,
                "date": 1700000000,
                "text": "Hello, OpenVK!",
                "type": "post",
                "comments": {
                    "count": 0,
                    "can_post": 1
                },
                "likes": {
                    "count": 5,
                    "user_likes": 1,
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
        "next_from": "1700000000_105"
    }
}
```
