OpenVK-KB-Heading: board.getComments

# board.getComments

Returns a list of comments in a community discussion topic.

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `group_id` | integer | Community identifier. **Required.** |
| `topic_id` | integer | Topic identifier inside the community. **Required.** |
| `need_likes` | boolean | `true` to return like counters for comments. Default: `false`. |
| `offset` | integer | Offset needed to return a specific subset of comments. Default: `0`. |
| `count` | integer | Number of comments to return (from `1` to `100`). Default: `10`. |
| `extended` | boolean | `true` to include `profiles` and `groups` arrays with author objects. Default: `false`. |

### Result

For API versions 5.0 and higher, returns an object containing:
* `count` (integer) — total number of comments in topic;
* `items` (array) — array of comment objects;
* `profiles` (array, optional) — array of user profiles when `extended = true`;
* `groups` (array, optional) — array of community objects when `extended = true`.

For API versions prior to 5.0, returns an object structured as `{"comments": [count, comment1, ...], "profiles": [...], "groups": [...]}`.

Each comment object contains:
* `id` (integer) — comment identifier;
* `from_id` (integer) — author identifier;
* `date` (integer) — publication time (Unix timestamp);
* `text` (string) — comment text;
* `attachments` (array, optional) — attachments (photos, videos, audios, stickers);
* `likes` (object, optional) — like counter object (`count`, `user_likes`, `can_like`).

### Error Codes

| Code | Description |
| --- | --- |
| `4` | `Invalid count` |
| `5` | `Not found` |

### Request Example
```http
POST /method/board.getComments HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&topic_id=1&need_likes=1&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 101,
                "from_id": 1,
                "date": 1609459200,
                "text": "Welcome to the discussion!",
                "likes": {
                    "count": 3,
                    "user_likes": 0,
                    "can_like": 1
                }
            }
        ],
        "profiles": [
            {
                "id": 1,
                "first_name": "Pavel",
                "last_name": "Durov",
                "photo_50": "/storage/avatars/1.jpg"
            }
        ],
        "groups": []
    }
}
```
