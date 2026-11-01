OpenVK-KB-Heading: photos.addComment

# photos.addComment

Adds a comment to a photo. Provided for backwards compatibility with legacy VK API clients (equivalent to [photos.createComment](/dev/methods/photos/createComment)).

### Authorization
Requires user authorization (`access_token`) with the `photos` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | **Required.** Photo owner ID (positive for user, negative for community). |
| `photo_id` | integer | Photo ID. |
| `pid` | integer | Alternative photo ID parameter (for backwards compatibility). |
| `message` | string | Comment text. |
| `text` | string | Alternative text parameter. |
| `reply_to_comment` | integer | ID of the comment to reply to. |
| `reply_to_cid` | integer | Alternative parent comment ID parameter. |
| `attachments` | string | Media attachments string. |
| `from_group` | integer | `1` — post on behalf of group, `0` — personal. |

### Result

Returns the ID (integer) of the created comment.

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Commenting restricted by privacy settings. |

### Example Request
```http
POST /method/photos.addComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&pid=1&message=Beautiful%20shot&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 16
}
```
