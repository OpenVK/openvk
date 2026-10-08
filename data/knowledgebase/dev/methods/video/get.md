OpenVK-KB-Heading: video.get

# video.get

Returns a list of videos for a user or community, or detailed information about specific videos by their identifiers.

### Authorization
This method does not require authorization for public videos. If `owner_id` is omitted, the ID of the current authorized user is used.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Identifier of the video owner (positive for user, negative for group). Default: current user ID. |
| `videos` | string | Comma-separated video IDs in `<owner_id>_<video_id>` format (up to 100 items), e.g. `1_45,-10_20`. |
| `video_id` | integer | Specific video identifier (used when `videos` is empty). |
| `gid` | integer | Community ID (alternative to negative `owner_id`). |
| `offset` | integer | Offset needed to return a specific subset of videos. Default: `0`. |
| `count` | integer | Number of videos to return (maximum `100`). Default: `30`. |
| `extended` | integer | `1` — return additional `profiles` and `groups` arrays, `0` — return video objects only. Default: `0`. |
| `fields` | string | Comma-separated list of additional profile and group fields (when `extended=1`). |

### Result

Returns an object with the following fields:

| Field | Type | Description |
| --- | --- | --- |
| `count` | integer | Total number of videos. |
| `items` | array | Array of video objects. |
| `profiles` | array | Authors' user profiles (when `extended=1`). |
| `groups` | array | Authors' communities (when `extended=1`). |

Each video object in `items` contains:
* `id` (integer) — video ID.
* `owner_id` (integer) — owner ID.
* `title` (string) — video title.
* `description` (string) — video description.
* `duration` (integer) — duration in seconds.
* `photo_320` (string) — video cover thumbnail URL.
* `date` (integer) — upload date in unixtime format.
* `views` (integer) — view count.
* `comments` (integer) — comment count.
* `player` (string) — embedded player URL.

### Possible Errors

| Code | Description |
| --- | --- |
| `14` | `Invalid user` — user or community not found or deleted. |
| `15` | `Too many ids given` — more than 100 IDs provided in `videos`. |
| `21` | `This user chose to hide his videos.` — access restricted by user's privacy settings. |

### Example Request
```http
POST /method/video.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&count=2&v=5.138
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
                "title": "Conference Keynote",
                "description": "Presentation recording",
                "duration": 720,
                "photo_320": "https://openvk.instance/videos/thumb_1_1.jpg",
                "date": 1775650000,
                "views": 150,
                "comments": 4,
                "player": "https://openvk.instance/video_ext.php?oid=1&id=1"
            }
        ]
    }
}
```
