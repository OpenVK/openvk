OpenVK-KB-Heading: video.getComments

# video.getComments

Returns a list of comments on a video.

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `video_id` | integer | **Required.** Video identifier. |
| `owner_id` | integer | Identifier of the video owner (positive for user, negative for group). Default: current user ID. |
| `need_likes` | integer | Whether to return likes information (default: `0`). |
| `offset` | integer | Offset needed to return a specific subset of comments. Default: `0`. |
| `count` | integer | Number of comments to return. Default: `20`. |
| `sort` | string | Sort order: `"asc"` — chronological (oldest first), `"desc"` — reverse chronological (newest first). Default: `"asc"`. |

### Result

Returns an object with the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `count` | integer | Total number of comments on the video. |
| `items` | array | Array of comment objects. |

Each comment object in `items` contains:
* `id` (integer) — comment identifier.
* `from_id` (integer) — comment author (positive for user, negative for group).
* `date` (integer) — publication timestamp in unixtime.
* `text` (string) — comment text.
* `reply_to_cid` (integer) — ID of the comment this is replying to (if applicable).
* `reply_to_uid` (integer) — author ID of the parent comment.
* `likes` (object) — likes information: `count` (number of likes), `user_likes` (whether current user liked it: `1` or `0`), `can_like` (`1`).

### Possible Errors

| Code | Description |
| --- | --- |
| `100` | `One of the parameters specified was missing or invalid: video not found` — video was not found or has been deleted. |

### Example Request
```http
POST /method/video.getComments HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&video_id=1&count=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 15,
                "cid": 15,
                "from_id": 2,
                "uid": 2,
                "date": 1775651200,
                "text": "Great video!",
                "message": "Great video!",
                "reply_to_cid": 0,
                "reply_to_uid": 0,
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
