OpenVK-KB-Heading: newsfeed.search

# newsfeed.search

Searches for wall posts across the global newsfeed matching a given query string.

### Authorization
This method can be called either by authorized users or guests (for guests, NSFW-marked posts are automatically filtered out).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `q` | string | Search query string to match in post contents. |
| `extended` | boolean | `1` (`true`) — return profiles and communities of post authors, `0` (`false`) — posts only. Default: `1`. |
| `count` | integer | Number of posts to return. Default: `30`. |
| `start_time` | integer | Earliest post timestamp (Unix time). Default: `0`. |
| `end_time` | integer | Latest post timestamp (Unix time). |
| `start_from` | string | Cursor identifier in `timestamp_id` format (from `next_from` / `new_from`). |
| `fields` | string | Comma-separated list of additional profile/group fields to return. |

### Result

Returns an object with the following fields:
* `count` (integer) — number of matching posts in this batch;
* `items` (array) — array of post objects (`type: "post"`);
* `profiles` (array) — array of user profiles (when `extended=1`);
* `groups` (array) — array of communities (when `extended=1`);
* `next_from` (string) — cursor to fetch the next page;
* `new_from` (string) — string cursor (for backward compatibility);
* `new_offset` (integer) — offset count.

### Errors

| Code | Description |
| --- | --- |
| `100` | `One of the parameters specified was missing or invalid.` — Invalid parameters passed. |

### Request Example
```http
POST /method/newsfeed.search HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=OpenVK&count=5&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 12,
                "owner_id": 1,
                "from_id": 1,
                "date": 1699990000,
                "text": "We have launched a new version of OpenVK!",
                "type": "post",
                "source_id": 1,
                "comments": {
                    "count": 3,
                    "can_post": 1
                },
                "likes": {
                    "count": 8,
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
        "next_from": "1699990000_12",
        "new_from": "1699990000_12",
        "new_offset": 1
    }
}
```
