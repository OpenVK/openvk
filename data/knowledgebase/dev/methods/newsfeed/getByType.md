OpenVK-KB-Heading: newsfeed.getByType

# newsfeed.getByType

Returns newsfeed items by specified feed type (`top` for global/popular feed, or standard user subscriptions feed).

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `feed_type` | string | Feed type. Possible values: `"top"` (global feed, delegates to [newsfeed.getGlobal](/dev/methods/newsfeed/getGlobal)), any other value delegates to [newsfeed.get](/dev/methods/newsfeed/get). Default: `"top"`. |
| `fields` | string | Comma-separated list of additional profile and group fields to return. |
| `start_from` | string / integer | Cursor identifier for pagination. |
| `start_time` | integer | Earliest timestamp (Unix time). Default: `0`. |
| `end_time` | integer | Latest timestamp (Unix time). |
| `offset` | integer | Items offset. Default: `0`. |
| `count` | integer | Number of items to return. Default: `30`. |
| `extended` | boolean | `1` (`true`) — return profiles and communities, `0` (`false`) — posts only. Default: `0`. |
| `return_banned` | boolean | `1` (`true`) — include banned/ignored sources when `feed_type="top"`. Default: `0`. |

### Result

Returns a feed object formatted in accordance with the delegated method (`newsfeed.getGlobal` or `newsfeed.get`).

### Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |

### Request Example
```http
POST /method/newsfeed.getByType HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

feed_type=top&count=10&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "items": [
            {
                "id": 101,
                "owner_id": 1,
                "from_id": 1,
                "date": 1700000000,
                "text": "Popular post",
                "type": "post",
                "source_id": 1,
                "comments": {
                    "count": 5,
                    "can_post": 1
                },
                "likes": {
                    "count": 20,
                    "user_likes": 1,
                    "can_like": 1
                }
            }
        ],
        "profiles": [],
        "groups": [],
        "next_from": "1700000000_101"
    }
}
```
