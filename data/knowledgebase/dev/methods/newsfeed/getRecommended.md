OpenVK-KB-Heading: newsfeed.getRecommended

# newsfeed.getRecommended

Returns a list of recommended wall posts for the user. In the current OpenVK implementation, this is a direct alias to [newsfeed.getGlobal](/dev/methods/newsfeed/getGlobal).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `fields` | string | Comma-separated list of additional profile and group fields to return. |
| `start_from` | string | Cursor identifier for pagination in `timestamp_id` format. |
| `start_time` | integer | Earliest timestamp (Unix time) to fetch posts from. Default: `0`. |
| `end_time` | integer | Latest timestamp (Unix time) to fetch posts up to. |
| `offset` | integer | Items offset. Default: `0`. |
| `count` | integer | Number of posts to return. Default: `30`. |
| `extended` | boolean | `1` (`true`) — return profiles and communities, `0` (`false`) — posts only. Default: `1`. |
| `rss` | boolean | `1` (`true`) — return results in RSS format. Default: `0`. |
| `return_banned` | boolean | `1` (`true`) — include ignored sources, `0` (`false`) — exclude. Default: `0`. |

### Result

Returns an object with the following fields:
* `items` (array) — array of post objects;
* `profiles` (array) — array of user profiles (when `extended=1`);
* `groups` (array) — array of communities (when `extended=1`);
* `next_from` (string) — cursor for the next page.

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Request Example
```http
POST /method/newsfeed.getRecommended HTTP/1.1
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
                "id": 50,
                "owner_id": -1,
                "from_id": -1,
                "date": 1700001000,
                "text": "Recommended post from a community",
                "type": "post",
                "source_id": -1,
                "comments": {
                    "count": 0,
                    "can_post": 1
                },
                "likes": {
                    "count": 12,
                    "user_likes": 0,
                    "can_like": 1
                }
            }
        ],
        "profiles": [],
        "groups": [
            {
                "id": 1,
                "name": "Official Community",
                "screen_name": "club1",
                "photo_50": "/assets/packages/static/openvk/img/community_50.png",
                "photo_100": "/assets/packages/static/openvk/img/community_100.png"
            }
        ],
        "next_from": "1700001000_50"
    }
}
```
