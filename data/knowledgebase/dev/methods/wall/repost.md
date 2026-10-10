OpenVK-KB-Heading: wall.repost

# wall.repost

Copies an object (wall post, photo, or video) to the current user's wall or a community wall.

### Authorization
Requires user authorization token with `wall` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `object` | string | **Required.** Identifier of the object to repost in `<type><owner>_<id>` format (e.g. `wall1_42`, `photo1_12`, `video-5_10`). |
| `message` | string | Accompanying comment text by the user. |
| `attachments` | string | Comma-separated list of additional attachments. |
| `group_id` | integer | Target community ID to publish repost on community wall. |
| `as_group` | integer | `1` — publish repost on behalf of the community (when `group_id` specified). |
| `signed` | integer | `1` — attach author signature to the community repost. |

### Result

Returns an object with repost information:

| Field | Type | Description |
| --- | --- | --- |
| `success` | integer | Always `1` on success. |
| `post_id` | integer | Identifier of the newly created repost. |
| `reposts_count` | integer | Total repost count of the source object. |
| `likes_count` | integer | Total like count of the source object. |

### Possible Errors

| Code | Description |
| --- | --- |
| `100` | `One of the parameters specified was missing or invalid: object is incorrect` — invalid or missing `object` parameter. |
| `16` | `Access to group denied` — user lacks permission to publish on community wall. |

### Example Request
```http
POST /method/wall.repost HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

object=wall1_10&message=Must+read!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "success": 1,
        "post_id": 43,
        "reposts_count": 2,
        "likes_count": 5
    }
}
```
