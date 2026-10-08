OpenVK-KB-Heading: video.search

# video.search

Returns a list of videos matching the search query.

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `q` | string | Search query string to match against video titles and descriptions. |
| `sort` | integer | Sort order: `0` — by upload date, `1` — by duration, `2` — by relevance. Default: `0`. |
| `offset` | integer | Offset needed to return a specific subset of videos. Default: `0`. |
| `count` | integer | Number of videos to return. Default: `10`. |
| `extended` | boolean / integer | `1` — return additional `profiles` and `groups` arrays, `0` — videos only. Default: `0`. |
| `fields` | string | Comma-separated list of profile and group fields (when `extended=1`). |

### Result

Returns an object with the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `count` | integer | Total number of found videos. |
| `items` | array | Array of video objects. |
| `profiles` | array | Authors' user profiles (when `extended=1`). |
| `groups` | array | Authors' communities (when `extended=1`). |

### Example Request
```http
POST /method/video.search HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=conference&count=5&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "owner_id": 1,
                "title": "Web Development Keynote",
                "description": "Presentation recording",
                "duration": 1240,
                "photo_320": "https://openvk.instance/videos/thumb_1_1.jpg",
                "date": 1775650000,
                "views": 320,
                "comments": 12,
                "player": "https://openvk.instance/video_ext.php?oid=1&id=1"
            }
        ]
    }
}
```
