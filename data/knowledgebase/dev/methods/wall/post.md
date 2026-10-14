OpenVK-KB-Heading: wall.post

# wall.post

Publishes a new post on a user or community wall, or publishes a previously suggested post.

### Authorization
Requires user authorization token with `wall` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Identifier of the wall owner (positive for user, negative for group). |
| `message` | string | Post text content. |
| `attachments` | string | Comma-separated list of attachments in `<type><owner>_<id>` format (e.g. `photo1_23,audio1_45`). |
| `from_group` | integer | `1` — publish on behalf of the community (community admins only). Default: `0`. |
| `signed` | integer | `1` — include author signature when publishing on behalf of a community. Default: `0`. |
| `copyright` | string | External source URL / copyright attribution link. |
| `post_id` | integer | Identifier of a suggested post in a community (to approve and publish it). |
| `explicit` | integer | `1` — flag post as 18+ content (NSFW). |
| `lat` | float | Geographical latitude of the post location. |
| `long` | float | Geographical longitude of the post location. |
| `place_name` | string | Name of the place/venue. |

### Result

Returns an object containing the identifier of the created post:

| Field | Type | Description |
| --- | --- | --- |
| `post_id` | integer | Numeric identifier of the created wall post. |

### Possible Errors

| Code | Description |
| --- | --- |
| `15` | `Access denied` — user does not have permission to post on the specified wall. |
| `18` | `User was deleted or banned` — target user has been deleted or banned. |

### Example Request
```http
POST /method/wall.post HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&message=Hello+world!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "post_id": 42
    }
}
```
