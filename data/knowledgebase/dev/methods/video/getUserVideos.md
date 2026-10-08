OpenVK-KB-Heading: video.getUserVideos

# video.getUserVideos

Returns a list of videos for a specified user.

> **Note:** This method is a convenient alias for [video.get](/dev/methods/video/get) filtered by user ID.

### Authorization
This method does not require authorization for publicly accessible videos.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_id` | integer | Target user ID. Default: current user ID. |
| `offset` | integer | Offset needed to return a specific subset of videos. Default: `0`. |
| `count` | integer | Number of videos to return. Default: `30`. |
| `extended` | integer | `1` — return profiles and groups, `0` — videos only. Default: `0`. |

### Result

Returns an object with `count` (total number of videos) and `items` (array of video objects).

### Example Request
```http
POST /method/video.getUserVideos HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&count=2&v=5.138
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
                "title": "My video",
                "description": "Video description",
                "duration": 300,
                "photo_320": "https://openvk.instance/videos/thumb_1_1.jpg",
                "date": 1775650000,
                "views": 42,
                "comments": 1,
                "player": "https://openvk.instance/video_ext.php?oid=1&id=1"
            }
        ]
    }
}
```
