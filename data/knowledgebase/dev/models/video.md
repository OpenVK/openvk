OpenVK-KB-Heading: Video Object

# Video Object

The **Video** object describes a video recording in VK / OpenVK. The structure of the returned fields depends on the API version passed in the `v` parameter.

---

## API version 5.0 and higher (v >= 5.0)

API versions 5.0+ use the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Video ID. |
| `owner_id` | integer | Video owner ID (user or community). |
| `title` | string | Video title. |
| `description` | string | Video text description. |
| `duration` | integer | Video duration in seconds. |
| `date` | integer | Video upload timestamp (Unix timestamp). |
| `views` | integer | Number of video views. |
| `comments` | integer | Number of comments on the video. |
| `player` | string | Embedded video player page URL (iframe). |
| `platform` | string | *(Optional)* External video platform (e.g. `"youtube"`). |
| `access_key` | string | Video access key. |
| `image` | array | Array of preview image objects (`url`, `width`, `height`). |
| `files` | object | Object containing direct MP4 links by quality (`mp4_240`, `mp4_360`, `mp4_480`, `mp4_720`, `mp4_1080`). |
| `likes` | object | *(Optional)* Likes information (`count`, `user_likes`). |
| `reposts` | object | Reposts information (`count`, `user_reposted`). |
| `can_comment` | integer | `1` if current user can leave comments. |
| `can_like` | integer | `1` if current user can like the video. |
| `can_repost` | integer | `1` if reposting is allowed. |

### Example Object (v >= 5.0)
```json
{
    "id": 84,
    "owner_id": 1,
    "title": "OpenVK Demo",
    "description": "Overview of key engine features",
    "duration": 120,
    "date": 1696680000,
    "views": 450,
    "comments": 12,
    "player": "https://openvk.instance/video_ext.php?oid=1&id=84&hash=abc123",
    "access_key": "abc123def",
    "image": [
        {
            "url": "https://openvk.instance/videos/thumbs/84.jpg",
            "width": 320,
            "height": 240
        }
    ],
    "likes": {
        "count": 25,
        "user_likes": 1
    }
}
```

---

## Before API version 5.0 (v < 5.0)

In API versions 3.x and 4.x, legacy `vid`, `image`, and `image_medium` fields are returned:

| Field | Type | Description |
| --- | --- | --- |
| `vid` | integer | Video ID (equivalent to `id` in API v5.0+). |
| `owner_id` | integer | Video owner ID. |
| `title` | string | Video title. |
| `description` | string | Video description. |
| `duration` | integer | Duration in seconds. |
| `date` | integer | Upload timestamp (Unix timestamp). |
| `views` | integer | View count. |
| `image` | string | Thumbnail URL 130x100px. |
| `image_medium` | string | Thumbnail URL 320x240px. |
| `player` | string | Player page URL. |

### Example Object (v < 5.0)
```json
{
    "vid": 84,
    "owner_id": 1,
    "title": "OpenVK Demo",
    "description": "Overview of key engine features",
    "duration": 120,
    "date": 1696680000,
    "views": 450,
    "image": "https://openvk.instance/videos/thumbs/84_130.jpg",
    "image_medium": "https://openvk.instance/videos/thumbs/84_320.jpg",
    "player": "https://openvk.instance/video_ext.php?oid=1&id=84&hash=abc123"
}
```
